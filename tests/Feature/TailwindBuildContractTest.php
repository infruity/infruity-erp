<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Kontrak build frontend (migrasi Bootstrap -> Tailwind, B0).
 *
 * - Tailwind tidak boleh lagi dimuat dari CDN (tidak untuk production, compile di browser).
 * - CSS Tailwind compiled harus paling akhir di <head>. CDN lama menyisipkan <style>-nya
 *   di akhir <head>, sehingga utility menang atas aturan lain dengan spesifisitas sama.
 *   Memindahkannya ke atas diam-diam mengubah hasil cascade.
 */
class TailwindBuildContractTest extends TestCase
{
    private const LAYOUTS = [
        'resources/views/layouts/erp.blade.php' => 'resources/css/app.css',
        'resources/views/template/root.blade.php' => 'resources/css/legacy.css',
        'resources/views/auth/login.blade.php' => 'resources/css/app.css',
        'resources/views/landing.blade.php' => 'resources/css/app.css',
    ];

    public function test_no_layout_loads_tailwind_or_alpine_from_a_cdn(): void
    {
        foreach (array_keys(self::LAYOUTS) as $path) {
            $source = file_get_contents(base_path($path));

            $this->assertStringNotContainsString('cdn.tailwindcss.com', $source, $path);
            $this->assertStringNotContainsString('tailwind.config', $source, $path.' memakai config inline');
            $this->assertDoesNotMatchRegularExpression('#alpinejs@|unpkg\.com/alpinejs|@alpinejs/collapse@#', $source, $path);
        }
    }

    public function test_compiled_tailwind_css_is_the_last_thing_in_head(): void
    {
        foreach (self::LAYOUTS as $path => $entry) {
            $source = file_get_contents(base_path($path));
            $head = substr($source, 0, strpos($source, '</head>'));

            $vite = "@vite('{$entry}')";
            $this->assertStringContainsString($vite, $head, "$path harus memuat $vite");

            $after = substr($head, strrpos($head, $vite) + strlen($vite));
            $this->assertDoesNotMatchRegularExpression(
                '#<link[^>]+stylesheet|<style|@vite\(|@include\(#i',
                $after,
                "$path: $vite harus paling akhir di <head>"
            );
        }
    }

    public function test_the_erp_layout_gets_jquery_swal_and_datatables_from_the_vendor_bundle(): void
    {
        $erp = file_get_contents(base_path('resources/views/layouts/erp.blade.php'));
        $head = substr($erp, 0, strpos($erp, '</head>'));

        $this->assertStringContainsString("@include('partials.vendor-js')", $head, 'jQuery/Swal/DataTables dimuat sinkron sebelum skrip halaman');
        $this->assertStringContainsString("@vite('resources/css/datatables.css')", $head);
        $this->assertStringNotContainsString('datatables.bundle', $erp, 'bundle DataTables Metronic (2,4 MB) tidak boleh kembali ke layout ERP');
        $this->assertStringNotContainsString('plugins.bundle', $erp);
        $this->assertStringNotContainsString('style.bundle', $erp);
    }

    public function test_only_the_app_layout_gets_the_13px_base_text(): void
    {
        $erp = file_get_contents(base_path('resources/views/layouts/erp.blade.php'));
        $login = file_get_contents(base_path('resources/views/auth/login.blade.php'));
        $landing = file_get_contents(base_path('resources/views/landing.blade.php'));
        $css = file_get_contents(base_path('resources/css/app.css'));
        $config = file_get_contents(base_path('tailwind.config.mjs'));

        $this->assertMatchesRegularExpression('/<body class="[^"]*\berp-app\b/', $erp);
        // Login juga memakai class erp-ui dan app.css, tetapi harus tetap 16px.
        $this->assertDoesNotMatchRegularExpression('/<body class="[^"]*\berp-app\b/', $login);
        $this->assertDoesNotMatchRegularExpression('/<body class="[^"]*\berp-app\b/', $landing);
        $this->assertMatchesRegularExpression('/body\.erp-app\s*\{\s*@apply text-body;/', $css);
        $this->assertStringContainsString("body: ['13px', '1.5']", $config);
    }

    public function test_erp_action_is_the_only_button_family(): void
    {
        // erp-button dilebur ke erp-action. .erp-action hidup di erp-theme.css karena halaman
        // mandiri (error, struk) hanya memuat berkas itu.
        $theme = file_get_contents(base_path('public/assets/css/erp-theme.css'));
        $workspace = file_get_contents(base_path('public/assets/css/erp-workspace.css'));

        $this->assertStringContainsString('.erp-action {', $theme);
        $this->assertStringNotContainsString('.erp-action {', $workspace);

        $roots = ['resources/views', 'Modules', 'public/assets/css', 'public/assets/js', 'resources/css', 'resources/js'];
        foreach ($roots as $root) {
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(base_path($root), \FilesystemIterator::SKIP_DOTS));
            foreach ($iterator as $file) {
                if (! preg_match('/\.(php|css|js)$/', $file->getFilename()) || str_contains($file->getPathname(), '/node_modules/')) {
                    continue;
                }
                $this->assertStringNotContainsString('erp-button', file_get_contents($file->getPathname()), $file->getPathname().' memakai erp-button; pakai erp-action');
            }
        }
    }
}
