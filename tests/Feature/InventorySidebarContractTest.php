<?php

namespace Tests\Feature;

use Tests\TestCase;

final class InventorySidebarContractTest extends TestCase
{
    private function sidebar(): string
    {
        return file_get_contents(base_path('resources/views/components/erp/sidebar.blade.php'));
    }

    public function test_pengadaan_and_produksi_live_under_inventaris(): void
    {
        $sidebar = $this->sidebar();

        $start = strpos($sidebar, '<span class="font-medium">Inventaris</span>');
        $end = strpos($sidebar, '<span class="font-medium">Penjualan</span>');
        $this->assertNotFalse($start);
        $this->assertNotFalse($end);
        $group = substr($sidebar, $start, $end - $start);

        $order = [
            "route('wholesale.index')",
            "route('production.index')",
            "route('sortir.index')",
            "route('transfer-pengirim.index')",
            "route('transfer-penerima.index')",
            "route('stock-opname.index')",
        ];
        $last = -1;
        foreach ($order as $link) {
            $position = strpos($group, $link);
            $this->assertNotFalse($position, "Inventaris harus memuat {$link}");
            $this->assertGreaterThan($last, $position, "Urutan menu salah di {$link}");
            $last = $position;
        }

        $this->assertStringContainsString('<span>Pengadaan</span>', $group);
        $this->assertStringContainsString('<span>Produksi</span>', $group);
    }

    public function test_redundant_pembelian_and_produksi_groups_are_gone(): void
    {
        $sidebar = $this->sidebar();

        $this->assertStringNotContainsString('<span class="font-medium">Pembelian</span>', $sidebar);
        $this->assertStringNotContainsString('<span class="font-medium">Produksi</span>', $sidebar);
        $this->assertStringNotContainsString('Produksi Barang', $sidebar);
    }

    public function test_inventaris_group_is_active_for_pengadaan_and_produksi_routes(): void
    {
        $this->assertMatchesRegularExpression(
            "/\\\$inventoryRoutes = \\[[^\\]]*'wholesale\\.\\*'[^\\]]*'production\\.\\*'|\\\$inventoryRoutes = \\[[^\\]]*'production\\.\\*'[^\\]]*'wholesale\\.\\*'/",
            $this->sidebar()
        );
    }

    public function test_page_titles_and_breadcrumb_use_inventaris_wording(): void
    {
        $wholesale = file_get_contents(base_path('Modules/Transaction/Resources/views/wholesale/index.blade.php'));
        $this->assertStringContainsString("@section('title', 'Pengadaan - Inventaris')", $wholesale);
        $this->assertStringContainsString('<span>Inventaris</span>', $wholesale);
        $this->assertStringNotContainsString('Pembelian', $wholesale);

        $form = file_get_contents(base_path('Modules/Transaction/Resources/views/wholesale/create2.blade.php'));
        $this->assertStringContainsString("@section('title', 'Pengadaan - Inventaris')", $form);
        $this->assertStringNotContainsString('<span>Pembelian</span>', $form);

        foreach (['index', 'create'] as $view) {
            $source = file_get_contents(base_path("Modules/Transaction/Resources/views/production/{$view}.blade.php"));
            $this->assertStringContainsString("@section('title', 'Produksi')", $source);
            $this->assertStringNotContainsString('Produksi Barang', $source);
        }
    }
}
