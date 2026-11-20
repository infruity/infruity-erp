<?php

namespace Modules\Pos\Services;

use Illuminate\Support\Facades\DB;
use Modules\Pos\Entities\PosModel;

final class DeliveryAddressBook
{
    public function initialize(PosModel $pos): void
    {
        // Also handles customers/legacy drafts created after the migration. Deleted
        // entries count as an initialized book, so removing all addresses stays empty.
        if (DB::table('pos_delivery_addresses')
            ->when($pos->customer_id, fn ($q) => $q->where('customer_id', $pos->customer_id),
                fn ($q) => $q->whereNull('customer_id')->where('pos_id', $pos->id))->exists()) return;
        $customer = $pos->customer;
        $addresses = $customer ? DB::table('customer_address')->where('customer_id', $customer->id)->pluck('address')->all() : [];
        array_unshift($addresses, $customer?->address, $pos->ongkir_address);
        $addresses = array_values(array_unique(array_filter(array_map(fn ($a) => trim((string) $a), $addresses), fn ($a) => $a !== '' && $a !== '-')));
        foreach ($addresses as $index => $address) {
            DB::table('pos_delivery_addresses')->insert([
                'customer_id' => $pos->customer_id, 'pos_id' => $pos->customer_id ? null : $pos->id,
                'name' => $customer?->name ?? 'Pelanggan Umum', 'phone' => $customer?->whatsapp,
                'address' => $address, 'is_default' => $index === 0, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    private function query(PosModel $pos)
    {
        return DB::table('pos_delivery_addresses')->whereNull('deleted_at')
            ->when($pos->customer_id, fn ($q) => $q->where('customer_id', $pos->customer_id),
                fn ($q) => $q->whereNull('customer_id')->where('pos_id', $pos->id));
    }

    public function state(PosModel $pos): array
    {
        $addresses = $this->query($pos)->orderBy('id')->get()->map(fn ($row) => [
            'id' => $row->id, 'name' => $row->name, 'phone' => $row->phone ?? '',
            'address' => $row->address, 'is_default' => (bool) $row->is_default,
        ])->all();
        $snapshot = $pos->delivery_address_snapshot;
        $selected = $snapshot['id'] ?? null;
        if (!$snapshot && trim((string) $pos->ongkir_address) !== '') {
            foreach ($addresses as $address) {
                if ($address['address'] === $pos->ongkir_address) {
                    $selected = $address['id'];
                    break;
                }
            }
        }

        $current = trim((string) $pos->ongkir_address);
        return ['addresses' => $addresses, 'selected_id' => $selected, 'selected' => $snapshot,
            'current_address' => !in_array($current, ['', '-'], true) ? $current : 'Belum ada alamat pengiriman. Klik untuk mengatur alamat.'];
    }

    // Called inside the controller's transaction after locking the POS and customer.
    public function save(PosModel $pos, array $data, ?int $id): array
    {
        $this->initialize($pos);
        if ($id) abort_unless($this->query($pos)->where('id', $id)->exists(), 404);
        $before = $this->state($pos);
        $first = count($before['addresses']) === 0;
        $data['is_default'] = $first || (bool) $data['is_default'];
        if ($data['is_default']) $this->query($pos)->update(['is_default' => false, 'updated_at' => now()]);
        $digits = preg_replace('/\D/', '', (string) ($data['phone'] ?? ''));
        $digits = preg_replace('/^(62|0)/', '', $digits);
        $values = [
            'name' => trim($data['name']), 'phone' => $digits !== '' ? '0'.$digits : null,
            'address' => trim($data['address']), 'is_default' => $data['is_default'], 'updated_at' => now(),
        ];
        if ($id) {
            $this->query($pos)->where('id', $id)->update($values);
        } else {
            $id = DB::table('pos_delivery_addresses')->insertGetId($values + [
                'customer_id' => $pos->customer_id, 'pos_id' => $pos->customer_id ? null : $pos->id,
                'created_at' => now(),
            ]);
        }
        // Saving keeps the previous selection, except for the first/unselected address.
        if (!$before['selected_id'] || (int) $before['selected_id'] === $id) $this->select($pos, $id);
        return $this->state($pos);
    }

    public function select(PosModel $pos, int $id): array
    {
        $row = $this->query($pos)->where('id', $id)->first();
        abort_unless($row, 404);
        $pos->delivery_address_snapshot = [
            'id' => $row->id, 'name' => $row->name, 'phone' => $row->phone ?? '', 'address' => $row->address,
        ];
        $pos->ongkir_address = $row->address;
        $pos->save();
        return $this->state($pos);
    }

    public function delete(PosModel $pos, int $id): array
    {
        $selected = (int) $this->state($pos)['selected_id'] === $id;
        abort_unless($this->query($pos)->where('id', $id)->update(['deleted_at' => now(), 'updated_at' => now()]), 404);
        if ($selected) {
            $next = $this->query($pos)->orderBy('id')->first();
            if ($next) return $this->select($pos, $next->id);
            $pos->delivery_address_snapshot = null;
            $pos->ongkir_address = null;
            $pos->save();
        }
        return $this->state($pos);
    }
}
