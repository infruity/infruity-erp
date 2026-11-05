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
        'resources/views/layouts/erp-tailwind.blade.php' => 'resources/css/app.css',
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

    public function test_page_scripts_still_get_jquery_and_swal_before_the_body(): void
    {
        $erp = file_get_contents(base_path('resources/views/layouts/erp-tailwind.blade.php'));
        $head = substr($erp, 0, strpos($erp, '</head>'));

        $vendor = strpos($head, "@include('partials.vendor-js')");
        $datatables = strpos($head, 'datatables.bundle.js');

        $this->assertNotFalse($vendor);
        $this->assertNotFalse($datatables);
        $this->assertLessThan($datatables, $vendor, 'jQuery harus dimuat sebelum DataTables');
    }
}
