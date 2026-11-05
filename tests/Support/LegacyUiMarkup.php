<?php

namespace Tests\Support;

/**
 * Penanda markup Bootstrap/Metronic/ikon lama untuk migrasi ke Tailwind
 * (docs/tailwind-migration-plan.md §8.3).
 *
 * Dipakai oleh NoLegacyUiMarkupTest dan scripts/ui-migrate/ratchet-snapshot.php, supaya
 * keduanya selalu memakai definisi yang sama.
 */
final class LegacyUiMarkup
{
    /** Nama penanda => regex. Lookaround dipakai agar `erp-input-group` tidak dianggap `input-group`. */
    public const MARKERS = [
        'data-bs' => '/data-bs-[a-z-]+/',
        'data-kt' => '/data-kt-[a-z-]+/',
        'keenicons' => '/(?<![\w-])ki-[a-z0-9]+(?:-[a-z0-9]+)*/',
        'bootstrap-icons' => '/(?<![\w-])bi bi-[a-z0-9-]+/',
        'fontawesome' => '/(?<![\w-])fa[srb]? fa-[a-z0-9-]+|(?<![\w-])fa-(?:solid|regular|brands) fa-[a-z0-9-]+/',
        'form-classes' => '/(?<![\w-])(?:form-control|form-select|form-check(?:-input|-label)?|input-group)(?![\w-])/',
        'btn' => '/(?<![\w-])btn(?: btn-[a-z-]+|-(?:primary|secondary|light|danger|success|warning|info|sm|lg|icon)(?![\w-])|-(?:active|light|bg|color|flex)-[a-z-]+)/',
        'badge-light' => '/(?<![\w-])badge-light-[a-z]+/',
        'modal-class' => '/class="[^"]*(?<![\w-])modal(?:-dialog|-content|-header|-body|-footer)?(?![\w-])/',
        'select2' => '/(?<![\w-])select2(?![\w-])/',
        'bootstrap-js' => '/bootstrap\.(?:Modal|Dropdown|Tooltip|Collapse|Tab|Offcanvas|Toast|Popover)|\.modal\(/',
        'kt-js' => '/(?<![\w])KT[A-Z][A-Za-z]+/',
        'bs-utilities' => '/(?<![\w-])(?:d-(?:none|flex|block|inline|grid)|fw-(?:bold|semibold|bolder|normal)|fs-[0-9]|text-muted)(?![\w-])/',
        'root-layout' => '/@extends\(\'template\.root\'\)/',
        'tailwind-cdn' => '/cdn\.tailwindcss\.com/',
        'hex-class' => '/(?<![\w-])(?:bg|text|border|ring|from|to|via|fill|stroke)-\[#[0-9a-fA-F]{3,8}\]/',
    ];

    /** Folder yang dipindai. */
    public const ROOTS = [
        'resources/views',
        'Modules/*/Resources/views',
        'app',
        'Modules/*/Http',
        'public/assets/js',
        'resources/js',
    ];

    /** Dikecualikan: sumber referensi, vendor bundle, view print/pdf (Q15), kode yang akan dihapus. */
    public const EXCLUDE = [
        'resources/views/template - Copy/',
        '.bundle.',
        '/custom/',
    ];

    /** @return array<string, array<string, int>> path relatif => [penanda => jumlah] */
    public static function scan(string $base): array
    {
        $result = [];
        foreach (self::files($base) as $path) {
            $source = (string) file_get_contents($base.'/'.$path);
            $hits = [];
            foreach (self::MARKERS as $name => $regex) {
                $count = preg_match_all($regex, $source);
                if ($count) {
                    $hits[$name] = $count;
                }
            }
            if ($hits) {
                $result[$path] = $hits;
            }
        }
        ksort($result);

        return $result;
    }

    /** @return list<string> */
    public static function files(string $base): array
    {
        $files = [];
        foreach (self::ROOTS as $root) {
            foreach (glob($base.'/'.$root, GLOB_ONLYDIR) ?: [] as $dir) {
                $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS));
                foreach ($iterator as $file) {
                    $path = str_replace($base.'/', '', $file->getPathname());
                    if (! preg_match('/\.(php|js)$/', $path) || self::excluded($path)) {
                        continue;
                    }
                    $files[] = $path;
                }
            }
        }
        sort($files);

        return array_values(array_unique($files));
    }

    private static function excluded(string $path): bool
    {
        foreach (self::EXCLUDE as $needle) {
            if (str_contains($path, $needle)) {
                return true;
            }
        }

        return false;
    }
}
