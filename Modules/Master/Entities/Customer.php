<?php

namespace Modules\Master\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use DB;
use App\Models\User;
use Modules\Crm\Entities\CustomerTier;

class Customer extends Model
{
    use HasFactory;

    protected $table = 'customer';
    protected $fillable = [
        'name',
        'address',
        'phone',
    ];

    protected static function newFactory()
    {
        return \Modules\Master\Database\factories\CustomerFactory::new();
    }

    public static function getCustomerNumber()
    {
        return \App\Support\DocumentNumber::next(self::class, 'code', 'PLG' . now()->format('ym'), 5);
    }

    public static function sanitizeWhatsapp(?string $phone): ?string
    {
        $phone = trim((string) $phone);
        if ($phone === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone);
        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '8')) {
            return '0' . $digits;
        }

        return $digits;
    }

    public static function normalizeWhatsapp(?string $phone): ?string
    {
        $sanitized = self::sanitizeWhatsapp($phone);
        if (!$sanitized) {
            return null;
        }

        if (str_starts_with($sanitized, '0')) {
            return '62' . substr($sanitized, 1);
        }

        if (str_starts_with($sanitized, '8')) {
            return '62' . $sanitized;
        }

        return $sanitized;
    }

    public static function isValidWhatsapp(?string $phone): bool
    {
        $normalized = self::normalizeWhatsapp($phone);

        return $normalized !== null && preg_match('/^62\d{8,13}$/', $normalized) === 1;
    }

    public static function normalizedWhatsappSql(string $column = 'whatsapp'): string
    {
        $cleaned = "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE($column, ''), '+', ''), '-', ''), ' ', ''), '(', ''), ')', '')";

        return "CASE
            WHEN $cleaned = '' THEN ''
            WHEN LEFT($cleaned, 1) = '0' THEN CONCAT('62', SUBSTRING($cleaned, 2))
            WHEN LEFT($cleaned, 1) = '8' THEN CONCAT('62', $cleaned)
            ELSE $cleaned
        END";
    }

    public static function whatsappExists(?string $phone, ?int $ignoreId = null): bool
    {
        $normalized = self::normalizeWhatsapp($phone);
        if (!$normalized) {
            return false;
        }

        return self::query()
            ->when($ignoreId, function ($query) use ($ignoreId) {
                $query->where('id', '!=', $ignoreId);
            })
            ->whereRaw(self::normalizedWhatsappSql('whatsapp') . ' = ?', [$normalized])
            ->exists();
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by', 'id_user');
    }

    public function customerTier()
    {
        return $this->belongsTo(CustomerTier::class, 'id', 'customer_id');
    }

    public static function getCustomerGraph()
    {
        $results = DB::select("
            WITH RECURSIVE days AS (
                SELECT 1 AS day
                UNION ALL
                SELECT day + 1 FROM days
                WHERE day + 1 <= DAY(LAST_DAY(CURDATE()))
            ),
            daily_counts AS (
                SELECT DAY(created_at) AS day, COUNT(*) AS total
                FROM customer
                WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())
                GROUP BY DAY(created_at)
            )
            SELECT 
                days.day AS hari,
                COALESCE(daily_counts.total, 0) AS total
            FROM days
            LEFT JOIN daily_counts ON days.day = daily_counts.day
            ORDER BY days.day
        ");

        return $results;
    }
}
