<?php

namespace Modules\Pos\Services;

use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * A successful payment attempt, saved in the same transaction as its ledger entry.
 * Call only while holding the corresponding pos_transaction row lock.
 */
class PaymentSubmission
{
    public static function fingerprint(array $data): string
    {
        return hash('sha256', json_encode([
            'transaction_id' => (int) $data['transaction_id'],
            'date' => date('Y-m-d', strtotime($data['date'])),
            'customer_id' => empty($data['customer_id']) ? null : (int) $data['customer_id'],
            'total_payment' => number_format((float) $data['total_payment'], 2, '.', ''),
            'payments' => array_map(static fn (array $item): array => [
                'payment_id' => (int) $item['payment_id'],
                'amount' => (string) ((int) str_replace('.', '', (string) $item['amount'])),
            ], $data['payments']),
        ], JSON_THROW_ON_ERROR));
    }

    public static function replay(int $posId, string $key, int $actorId, string $fingerprint): ?array
    {
        $submission = DB::table('pos_payment_submissions')
            ->where('pos_id', $posId)->where('request_key', $key)->first();

        if (! $submission) {
            return null;
        }
        if ((int) $submission->user_id !== $actorId || ! hash_equals($submission->request_hash, $fingerprint)) {
            throw new ConflictHttpException('Percobaan pembayaran ini sudah digunakan. Muat ulang pembayaran sebelum membuat pembayaran baru.');
        }

        return json_decode($submission->response, true, 512, JSON_THROW_ON_ERROR);
    }

    public static function remember(int $posId, string $key, int $actorId, string $fingerprint, int $paymentId, array $response): void
    {
        DB::table('pos_payment_submissions')->insert([
            'pos_id' => $posId,
            'request_key' => $key,
            'user_id' => $actorId,
            'request_hash' => $fingerprint,
            'payment_id' => $paymentId,
            'response' => json_encode($response, JSON_THROW_ON_ERROR),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
