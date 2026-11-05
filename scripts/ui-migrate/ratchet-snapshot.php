<?php

/**
 * Tulis ulang tests/fixtures/legacy-ui-allowlist.json dari kondisi kode sekarang.
 *
 *   php scripts/ui-migrate/ratchet-snapshot.php            # tulis
 *   php scripts/ui-migrate/ratchet-snapshot.php --summary  # hanya ringkasan per penanda
 *
 * Jalankan setiap selesai memigrasi halaman supaya batas turun. Test hanya gagal bila
 * jumlah NAIK atau file baru memuat penanda; jangan menaikkan angka untuk meloloskan test.
 */

require __DIR__.'/../../tests/Support/LegacyUiMarkup.php';

use Tests\Support\LegacyUiMarkup;

$base = dirname(__DIR__, 2);
$scan = LegacyUiMarkup::scan($base);

if (in_array('--summary', $argv, true)) {
    $totals = [];
    foreach ($scan as $hits) {
        foreach ($hits as $marker => $count) {
            $totals[$marker] = ($totals[$marker] ?? 0) + $count;
        }
    }
    arsort($totals);
    printf("%d file memuat penanda.\n", count($scan));
    foreach ($totals as $marker => $count) {
        printf("  %-16s %6d\n", $marker, $count);
    }
    exit(0);
}

$target = $base.'/tests/fixtures/legacy-ui-allowlist.json';
file_put_contents($target, json_encode($scan, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");
printf("Allowlist ditulis: %d file, %s\n", count($scan), str_replace($base.'/', '', $target));
