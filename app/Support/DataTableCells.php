<?php

namespace App\Support;

use Illuminate\Support\Facades\Blade;

/**
 * HTML sel DataTables dari komponen Blade x-erp.* (output di-escape oleh komponen),
 * supaya controller tidak merakit string Bootstrap/Metronic sendiri.
 */
final class DataTableCells
{
    /** @param array<int, array<string, mixed>> $actions lihat x-erp.row-actions */
    public static function rowActions(array $actions, string $label = 'Aksi'): string
    {
        return Blade::render('<x-erp.row-actions :actions="$actions" :label="$label" copies />', compact('actions', 'label'));
    }

    public static function title(string $title, ?string $subtitle = null, ?string $href = null): string
    {
        return Blade::render('<x-erp.cell-title :title="$title" :subtitle="$subtitle" :href="$href" />', compact('title', 'subtitle', 'href'));
    }

    /** Menu Ubah/Hapus untuk satu baris; izin diperiksa oleh pemanggil. */
    public static function editDeleteMenu(string $name, int|string $id, bool $canEdit, bool $canDelete): string
    {
        return self::rowActions([
            ['label' => 'Ubah', 'icon' => 'pencil-simple', 'onclick' => 'editProduct('.(int) $id.')', 'can' => $canEdit],
            ['label' => 'Hapus', 'icon' => 'trash', 'onclick' => 'deleteProduct('.(int) $id.')', 'can' => $canDelete, 'tone' => 'danger'],
        ], 'Aksi '.$name);
    }
}
