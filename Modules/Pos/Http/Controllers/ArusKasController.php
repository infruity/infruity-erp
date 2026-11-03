<?php

namespace Modules\Pos\Http\Controllers;

use App\Traits\HasAccessControl;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Master\Entities\Branch;
use Modules\Master\Entities\UserBranch;
use Modules\Pos\Entities\ArusKasItem;
use Modules\Pos\Entities\ArusKasTransaction;
use Modules\Pos\Entities\Expenditure;
use Modules\Pos\Entities\ExpenditureDetail;

class ArusKasController extends Controller
{
    use HasAccessControl;

    public function index()
    {
        if ($denied = $this->requireAccess('expenditure.index')) {
            return $denied;
        }

        return $this->page('list');
    }

    public function create()
    {
        if ($denied = $this->requireAccess('expenditure.create')) {
            return $denied;
        }

        return $this->page('create');
    }

    public function edit(int $id)
    {
        if ($denied = $this->requireAccess('expenditure.edit')) {
            return $denied;
        }

        $transaction = $this->scopedTransaction($id);
        $formOrder = $this->mapNewTransaction($transaction, $this->userNames([$transaction->created_by])[(string) $transaction->created_by] ?? '—');

        return $this->page('edit', $formOrder);
    }

    public function store(Request $request)
    {
        if ($denied = $this->requireAccess('expenditure.create')) {
            return $denied;
        }

        $data = $this->validatedPayload($request);

        DB::transaction(function () use ($data) {
            $this->createTransaction($data['branch_id'], $data['status'], $data['items']);
        });

        return redirect()->route('arus-kas.index')->with('success', 'Transaksi arus kas berhasil disimpan.');
    }

    public function update(Request $request, int $id)
    {
        if ($denied = $this->requireAccess('expenditure.update')) {
            return $denied;
        }

        $transaction = $this->scopedTransaction($id);
        $data = $this->validatedPayload($request);

        DB::transaction(function () use ($transaction, $data) {
            $transaction->update([
                'branch_id' => $data['branch_id'],
                'status' => $data['status'],
                'updated_by' => auth()->id(),
            ]);
            $transaction->items()->delete();
            $this->createItems($transaction, $data['items']);
        });

        return redirect()->route('arus-kas.index')->with('success', 'Transaksi arus kas berhasil diperbarui.');
    }

    public function destroy(int $id)
    {
        if ($denied = $this->requireAccess('expenditure.destroy')) {
            return $denied;
        }

        $transaction = $this->scopedTransaction($id);
        $transaction->delete();

        return response()->json(['success' => true]);
    }

    public function updateStatus(Request $request, int $id)
    {
        if ($denied = $this->requireAccess('expenditure.update')) {
            return $denied;
        }

        $data = $request->validate(['status' => 'required|in:draft,completed']);
        $transaction = $this->scopedTransaction($id);

        if ($data['status'] === 'completed' && $transaction->items()->doesntExist()) {
            throw ValidationException::withMessages(['status' => 'Tambahkan minimal satu item kas sebelum menyelesaikan transaksi.']);
        }

        $transaction->update([
            'status' => $data['status'],
            'updated_by' => auth()->id(),
        ]);

        return response()->json(['success' => true, 'status' => $data['status']]);
    }

    private function page(string $mode, ?array $formOrder = null)
    {
        $branchIds = $this->branchIds();
        $branches = Branch::query()
            ->whereIn('id', $branchIds)
            ->orderBy('name')
            ->get(['id', 'name']);

        $orders = $mode === 'list' ? $this->listOrders($branchIds) : [];

        $selectedBranchId = old('branch_id', $formOrder['branchId'] ?? $branches->first()?->id);
        if (in_array($mode, ['create', 'edit'], true) && old('items_json') !== null) {
            $oldItems = json_decode((string) old('items_json'), true);
            if (is_array($oldItems)) {
                $formOrder = array_merge($formOrder ?? [], [
                    'branchId' => (string) old('branch_id', $selectedBranchId ?? ''),
                    'items' => $oldItems,
                ]);
            }
        }

        return view('pos::arus-kas.index', [
            'pageMode' => $mode,
            'orders' => $orders,
            'branches' => $branches,
            'formOrder' => $formOrder,
            'selectedBranchId' => $selectedBranchId,
        ]);
    }

    private function listOrders(array $branchIds): array
    {
        $newTransactions = ArusKasTransaction::with(['items', 'branch'])
            ->whereIn('branch_id', $branchIds)
            ->get();

        $legacyTransactions = Expenditure::with('branch')
            ->whereIn('branch_id', $branchIds)
            ->whereIn('type', ['pemasukan', 'pengeluaran'])
            ->where('status', '!=', 'canceled')
            ->get();

        $legacyDetails = ExpenditureDetail::query()
            ->whereIn('expenditure_id', $legacyTransactions->pluck('id'))
            ->orderBy('id')
            ->get()
            ->groupBy('expenditure_id');

        $staffIds = $newTransactions->pluck('created_by')
            ->merge($legacyTransactions->pluck('created_by'))
            ->filter()
            ->unique()
            ->values()
            ->all();
        $staffNames = $this->userNames($staffIds);

        $newOrders = $newTransactions
            ->map(fn (ArusKasTransaction $transaction) => $this->mapNewTransaction(
                $transaction,
                $staffNames[(string) $transaction->created_by] ?? '—'
            ))
            ->all();
        $legacyOrders = $legacyTransactions->map(function (Expenditure $transaction) use ($legacyDetails, $staffNames) {
                $items = ($legacyDetails->get($transaction->id) ?? collect())->map(function (ExpenditureDetail $detail) use ($transaction) {
                    return [
                        'type' => $transaction->type === 'pemasukan' ? 'in' : 'out',
                        'name' => $detail->product_name ?: 'Item kas',
                        'unit' => $detail->product_unit,
                        'quantity' => (float) $detail->quantity,
                        'price' => (float) $detail->price,
                        'subtotal' => (float) $detail->subtotal,
                    ];
                })->values()->all();
                $status = $transaction->status === 'draft' ? 'Draft' : 'Selesai';
                $firstItem = $items[0] ?? null;
                $activity = $firstItem['name'] ?? ($status === 'Draft' ? 'Draft Arus Kas' : 'Transaksi Arus Kas');
                if (count($items) > 1) {
                    $activity .= ' +' . (count($items) - 1) . ' lainnya';
                }
                $createdAt = $transaction->created_at ?? $transaction->date;
                $total = (float) ($transaction->total ?? collect($items)->sum('subtotal'));
                $paid = (float) ($transaction->paid ?? 0);

                return [
                    'id' => 'legacy-' . $transaction->id,
                    'source' => 'legacy',
                    'canEdit' => false,
                    'canDelete' => false,
                    'poNumber' => $transaction->invoice_number ?: 'EXP-' . $transaction->id,
                    'activity' => $activity,
                    'flowType' => $transaction->type === 'pemasukan' ? 'in' : 'out',
                    'status' => $status,
                    'branchId' => (string) $transaction->branch_id,
                    'targetBranch' => $transaction->branch?->name ?? '—',
                    'staffName' => $staffNames[(string) $transaction->created_by] ?? '—',
                    'supplierName' => null,
                    'supplierPhone' => null,
                    'supplierCity' => null,
                    'date' => $createdAt ? \Illuminate\Support\Carbon::parse($createdAt)->format('Y-m-d') : '',
                    'time' => $createdAt ? \Illuminate\Support\Carbon::parse($createdAt)->format('H:i') : '',
                    'paymentStatus' => $paid >= $total && $total > 0 ? 'Lunas' : ($paid > 0 ? 'DP' : 'Belum Bayar'),
                    'paymentMethod' => null,
                    'etaDate' => $transaction->date ? \Illuminate\Support\Carbon::parse($transaction->date)->format('Y-m-d') : '',
                    'items' => $items,
                    'grandTotal' => $total,
                    'sortAt' => $createdAt ? \Illuminate\Support\Carbon::parse($createdAt)->timestamp : 0,
                ];
            })->all();
        $orders = collect($newOrders)->merge($legacyOrders)
            ->sortByDesc('sortAt')
            ->values()
            ->map(function (array $order) {
                unset($order['sortAt']);
                return $order;
            })
            ->all();

        return $orders;
    }

    private function mapNewTransaction(ArusKasTransaction $transaction, string $staffName): array
    {
        $items = $transaction->items->map(fn (ArusKasItem $item) => [
            'type' => $item->direction,
            'name' => $item->description,
            'unit' => $item->unit,
            'quantity' => (float) $item->quantity,
            'price' => (float) $item->unit_price,
            'subtotal' => (float) $item->subtotal,
        ])->values()->all();
        $status = $transaction->status === 'draft' ? 'Draft' : 'Selesai';
        $activity = $items[0]['name'] ?? 'Draft Arus Kas';
        if (count($items) > 1) {
            $activity .= ' +' . (count($items) - 1) . ' lainnya';
        }

        return [
            'id' => (string) $transaction->id,
            'source' => 'arus-kas',
            'canEdit' => true,
            'canDelete' => true,
            'poNumber' => $transaction->invoice_number,
            'activity' => $activity,
            'flowType' => $items[0]['type'] ?? 'out',
            'status' => $status,
            'branchId' => (string) $transaction->branch_id,
            'targetBranch' => $transaction->branch?->name ?? '—',
            'staffName' => $staffName,
            'supplierName' => null,
            'supplierPhone' => null,
            'supplierCity' => null,
            'date' => $transaction->date?->format('Y-m-d') ?? '',
            'time' => $transaction->created_at?->format('H:i') ?? '',
            'paymentStatus' => $status === 'Selesai' ? 'Lunas' : 'Belum Bayar',
            'paymentMethod' => $status === 'Selesai' ? 'Kas Operasional' : null,
            'etaDate' => $transaction->date?->format('Y-m-d') ?? '',
            'items' => $items,
            'grandTotal' => collect($items)->sum('subtotal'),
            'sortAt' => $transaction->created_at?->timestamp ?? 0,
        ];
    }

    private function userNames(array $userIds): array
    {
        if ($userIds === []) {
            return [];
        }

        return DB::table('users')
            ->whereIn('id_user', $userIds)
            ->pluck('nm_user', 'id_user')
            ->mapWithKeys(fn ($name, $id) => [(string) $id => $name])
            ->all();
    }

    private function branchIds(): array
    {
        return array_values(array_unique(array_map('intval', UserBranch::getUserBranch())));
    }

    private function scopedTransaction(int $id): ArusKasTransaction
    {
        return ArusKasTransaction::with(['items', 'branch'])
            ->whereIn('branch_id', $this->branchIds())
            ->findOrFail($id);
    }

    private function validatedPayload(Request $request): array
    {
        $data = $request->validate([
            'branch_id' => 'required|integer',
            'status' => 'required|in:draft,completed',
            'items_json' => 'required|string',
        ]);

        $branchId = (int) $data['branch_id'];
        abort_unless(in_array($branchId, $this->branchIds(), true), 403);

        $items = json_decode($data['items_json'], true);
        if (! is_array($items)) {
            throw ValidationException::withMessages(['items_json' => 'Data item kas tidak valid.']);
        }

        $validator = Validator::make(['items' => $items], [
            'items' => 'present|array',
            'items.*.type' => 'required|in:in,out',
            'items.*.name' => 'required|string|max:255',
            'items.*.unit' => 'nullable|string|max:60',
            'items.*.quantity' => 'required|numeric|gt:0',
            'items.*.price' => 'required|numeric|min:0',
        ]);
        $validator->after(function ($validator) use ($items, $data) {
            if ($data['status'] === 'completed' && count($items) === 0) {
                $validator->errors()->add('items', 'Silakan tambahkan minimal 1 item kas terlebih dahulu.');
            }
        });
        $validator->validate();

        return [
            'branch_id' => $branchId,
            'status' => $data['status'],
            'items' => array_map(fn (array $item) => [
                'type' => $item['type'],
                'name' => trim($item['name']),
                'unit' => isset($item['unit']) && $item['unit'] !== '' ? trim($item['unit']) : null,
                'quantity' => (float) $item['quantity'],
                'price' => (float) $item['price'],
            ], $items),
        ];
    }

    private function createTransaction(int $branchId, string $status, array $items): ArusKasTransaction
    {
        $transaction = ArusKasTransaction::create([
            'uuid' => (string) Str::uuid(),
            'invoice_number' => $this->nextReference(),
            'branch_id' => $branchId,
            'date' => now()->toDateString(),
            'status' => $status,
            'created_by' => auth()->id(),
        ]);

        $this->createItems($transaction, $items);

        return $transaction;
    }

    private function createItems(ArusKasTransaction $transaction, array $items): void
    {
        foreach ($items as $item) {
            $transaction->items()->create([
                'direction' => $item['type'],
                'description' => $item['name'],
                'unit' => $item['unit'] ?? null,
                'quantity' => $item['quantity'],
                'unit_price' => $item['price'],
                'subtotal' => round($item['quantity'] * $item['price'], 2),
            ]);
        }
    }

    private function nextReference(): string
    {
        return 'AK-' . now()->format('Y') . '-' . Str::upper(Str::random(8));
    }
}
