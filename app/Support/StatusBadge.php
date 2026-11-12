<?php

namespace App\Support;

/**
 * The one answer to "which colour is this status?" and the one way to draw a status pill from PHP (DataTables columns
 * build their HTML in controllers). Blade views use <x-erp.badge>, which asks this class for the tone as well.
 *
 * Tones: success (green), warning (amber), danger (red), info (blue), primary (brand), neutral (grey).
 * Colours live in public/assets/css/erp-fields.css (.erp-badge--tone) on the ERP theme tokens, the same ones the legacy
 * Metronic "badge-light-*" classes already use, so old and new pills look alike.
 */
final class StatusBadge
{
    public const TONES = ['success', 'warning', 'danger', 'info', 'primary', 'neutral'];

    /** status word (lowercase) => tone. What a status means is decided here, not in each controller. */
    private const STATUS = [
        // done
        'selesai' => 'success', 'complete' => 'success', 'completed' => 'success', 'paid' => 'success', 'lunas' => 'success',
        'posting' => 'success', 'diterima' => 'success', 'received' => 'success', 'aktif' => 'success', 'active' => 'success',
        'done' => 'success', 'berhasil' => 'success',
        // under way
        'proses' => 'warning', 'process' => 'warning', 'diproses' => 'warning', 'debt' => 'warning', 'hutang' => 'warning',
        'partial' => 'warning', 'parsial' => 'warning',
        // not finished or stopped
        'temp' => 'danger', 'draft' => 'danger', 'batal' => 'danger', 'dibatalkan' => 'danger', 'canceled' => 'danger',
        'cancelled' => 'danger', 'rejected' => 'danger', 'ditolak' => 'danger', 'nonaktif' => 'danger', 'inactive' => 'danger',
        'gagal' => 'danger', 'failed' => 'danger',
        // on the way
        'dikirim' => 'info', 'sent' => 'info',
        // waiting
        'pending' => 'neutral', 'menunggu' => 'neutral',
    ];

    public static function tone(?string $status): string
    {
        return self::STATUS[mb_strtolower(trim((string) $status))] ?? 'neutral';
    }

    /** <span class="erp-badge erp-badge--tone">label</span>; the label is escaped, $class adds utility classes (ms-2, ...). */
    public static function html(?string $label, string $tone = 'neutral', string $class = ''): string
    {
        $tone = in_array($tone, self::TONES, true) ? $tone : 'neutral';
        $class = trim($class) !== '' ? ' ' . e(trim($class)) : '';

        return '<span class="erp-badge erp-badge--' . $tone . $class . '">' . e((string) $label) . '</span>';
    }

    /** A pill for a status word: the tone comes from the status, the label defaults to the status itself. */
    public static function for(?string $status, ?string $label = null, string $class = ''): string
    {
        return self::html($label ?? ucfirst((string) $status), self::tone($status), $class);
    }
}
