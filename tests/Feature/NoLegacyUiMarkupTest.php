<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;
use Tests\Support\LegacyUiMarkup;

/**
 * Penjaga migrasi Bootstrap/Metronic -> Tailwind (docs/tailwind-migration-plan.md §8.3, Q19).
 *
 * UI baru wajib memakai layout dan komponen Tailwind. Test ini gagal bila:
 *  - sebuah file yang belum terdaftar memuat markup lama, atau
 *  - jumlah penanda di file terdaftar NAIK.
 *
 * Saat memigrasi halaman, jumlahnya turun; perbarui batas dengan:
 *   php scripts/ui-migrate/ratchet-snapshot.php
 * Akhir migrasi (B6): allowlist kosong.
 */
class NoLegacyUiMarkupTest extends TestCase
{
    public function test_no_new_legacy_markup_and_no_file_gets_worse(): void
    {
        $base = dirname(__DIR__, 2);
        $allowed = json_decode((string) file_get_contents($base.'/tests/fixtures/legacy-ui-allowlist.json'), true) ?: [];
        $problems = [];

        foreach (LegacyUiMarkup::scan($base) as $path => $hits) {
            foreach ($hits as $marker => $count) {
                $limit = $allowed[$path][$marker] ?? 0;
                if ($count > $limit) {
                    $problems[] = sprintf('%s: "%s" %d (batas %d)', $path, $marker, $count, $limit);
                }
            }
        }

        $this->assertSame(
            [],
            $problems,
            "Markup Bootstrap/Metronic baru terdeteksi. Pakai layouts.erp dan komponen x-erp.* (docs/ui-design-system.md).\n".implode("\n", $problems)
        );
    }

    public function test_allowlist_has_no_entries_for_files_that_no_longer_exist(): void
    {
        $base = dirname(__DIR__, 2);
        $allowed = json_decode((string) file_get_contents($base.'/tests/fixtures/legacy-ui-allowlist.json'), true) ?: [];

        $missing = array_values(array_filter(array_keys($allowed), fn ($p) => ! file_exists($base.'/'.$p)));

        $this->assertSame([], $missing, "File sudah dihapus; jalankan php scripts/ui-migrate/ratchet-snapshot.php\n".implode("\n", $missing));
    }

    public function test_markers_do_not_confuse_erp_classes_with_legacy_ones(): void
    {
        $clean = 'class="erp-input-group erp-badge erp-table erp-dialog erp-check"';
        $legacy = 'class="input-group form-control btn btn-primary" data-bs-toggle="modal"';

        $hits = fn (string $s) => array_filter(array_map(fn ($re) => preg_match($re, $s), LegacyUiMarkup::MARKERS));

        $this->assertSame([], $hits($clean));
        $this->assertArrayHasKey('form-classes', $hits($legacy));
        $this->assertArrayHasKey('btn', $hits($legacy));
        $this->assertArrayHasKey('data-bs', $hits($legacy));
    }
}
