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

    public static function badge(string $label, string $tone = 'neutral'): string
    {
        return Blade::render('<x-erp.badge :tone="$tone">{{ $label }}</x-erp.badge>', compact('label', 'tone'));
    }

    /** Badge cabang: warna mengikuti id cabang 1-4, selain itu merah dengan teks $fallback. */
    public static function branchBadge(int|string|null $branchId, ?string $name, string $fallback): string
    {
        return match ((int) $branchId) {
            1 => self::badge((string) $name, 'primary'),
            2 => self::badge((string) $name, 'success'),
            3 => self::badge((string) $name, 'warning'),
            4 => self::badge((string) $name, 'info'),
            default => self::badge($fallback, 'danger'),
        };
    }

    public static function genderBadge(?string $gender): string
    {
        return match ($gender) {
            'male' => self::badge('Laki-laki', 'primary'),
            'female' => self::badge('Perempuan', 'success'),
            default => self::badge('-', 'danger'),
        };
    }

    /** Menu satu item: tautan ke halaman detail. */
    public static function viewMenu(string $href, string $label = 'Lihat detail', string $menuLabel = 'Aksi'): string
    {
        return self::rowActions([['label' => $label, 'icon' => 'eye', 'href' => $href]], $menuLabel);
    }
}
