<?php

namespace Modules\Pos\Http\Controllers;

use App\Traits\HasAccessControl;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Master\Entities\Customer;
use Modules\Master\Entities\UserBranch;
use Modules\Pos\Entities\PosModel;
use Modules\Pos\Services\DeliveryAddressBook;

final class PosDeliveryAddressController extends Controller
{
    use HasAccessControl;

    private function transaction(int $id, bool $lock = false): PosModel
    {
        $this->authorizeAccess('pos.payment');
        $pos = PosModel::whereIn('branch_id', UserBranch::getUserBranch())
            ->when($lock, fn ($q) => $q->lockForUpdate())->findOrFail($id);
        abort_unless(in_array($pos->status, ['temp', 'draft', 'pending'], true), 422, 'Alamat transaksi ini tidak dapat diubah.');
        // Serializes default-address changes across this customer's transactions.
        if ($lock && $pos->customer_id) Customer::whereKey($pos->customer_id)->lockForUpdate()->firstOrFail();
        return $pos;
    }

    public function index(int $id, DeliveryAddressBook $book)
    {
        return response()->json(DB::transaction(function () use ($id, $book) {
            $pos = $this->transaction($id, true);
            $book->initialize($pos);
            return $book->state($pos);
        }));
    }

    public function save(Request $request, int $id, DeliveryAddressBook $book, ?int $address = null)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255', 'phone' => ['required', 'string', 'max:15', 'regex:/^[0-9]+$/'],
            'address' => 'required|string|max:2000', 'is_default' => 'required|boolean',
        ]);
        return response()->json(DB::transaction(fn () => $book->save($this->transaction($id, true), $data, $address)));
    }

    public function select(int $id, int $address, DeliveryAddressBook $book)
    {
        return response()->json(DB::transaction(fn () => $book->select($this->transaction($id, true), $address)));
    }

    public function destroy(int $id, int $address, DeliveryAddressBook $book)
    {
        return response()->json(DB::transaction(fn () => $book->delete($this->transaction($id, true), $address)));
    }
}
