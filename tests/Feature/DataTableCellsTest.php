<?php

namespace Tests\Feature;

use App\Support\DataTableCells;
use Tests\TestCase;

/** HTML sel DataTables dari komponen Blade (menggantikan string Bootstrap di controller). */
class DataTableCellsTest extends TestCase
{
    public function test_title_escapes_user_data(): void
    {
        $html = DataTableCells::title('Gudang <b>Utama</b>', 'Jl. "Mawar"');

        $this->assertStringContainsString('Gudang &lt;b&gt;Utama&lt;/b&gt;', $html);
        $this->assertStringNotContainsString('<b>Utama</b>', $html);
        $this->assertStringContainsString('Jl. &quot;Mawar&quot;', $html);
    }

    public function test_edit_delete_menu_renders_both_items_with_danger_tone_and_no_bootstrap(): void
    {
        $html = DataTableCells::editDeleteMenu('Gudang A', 7, true, true);

        $this->assertStringContainsString('onclick="editProduct(7)"', $html);
        $this->assertStringContainsString('onclick="deleteProduct(7)"', $html);
        $this->assertStringContainsString('erp-menu__item--danger', $html);
        $this->assertStringContainsString('aria-label="Aksi Gudang A"', $html);
        $this->assertStringNotContainsString('data-bs-toggle', $html);
        $this->assertStringNotContainsString('bi-', $html);
    }

    public function test_menu_only_contains_permitted_items_and_nothing_when_none(): void
    {
        $editOnly = DataTableCells::editDeleteMenu('X', 1, true, false);
        $this->assertStringContainsString('editProduct(1)', $editOnly);
        $this->assertStringNotContainsString('deleteProduct(1)', $editOnly);

        $this->assertSame('', trim(DataTableCells::editDeleteMenu('X', 1, false, false)));
    }

    public function test_menu_carries_hidden_copies_for_the_mobile_card_list(): void
    {
        // Panel menu di-teleport ke <body>; erp-collections.js membuat tombol kartu dari salinan ini.
        $html = DataTableCells::editDeleteMenu('X', 3, true, true);

        $this->assertMatchesRegularExpression('/<span hidden data-erp-action-copies>.*editProduct\(3\).*deleteProduct\(3\).*<\/span>/s', $html);
        $this->assertStringContainsString('data-erp-row-actions', $html);
    }

    public function test_row_id_is_cast_to_an_integer_in_the_handler(): void
    {
        $html = DataTableCells::editDeleteMenu('X', "1);alert(1", true, true);

        $this->assertStringNotContainsString('alert(1', $html);
    }

    public function test_badges_escape_labels_and_pick_the_tone_by_branch(): void
    {
        $this->assertStringContainsString('erp-badge--success', DataTableCells::branchBadge(2, 'Cabang <b>B</b>', 'Other'));
        $this->assertStringContainsString('Cabang &lt;b&gt;B&lt;/b&gt;', DataTableCells::branchBadge(2, 'Cabang <b>B</b>', 'Other'));
        $unknown = DataTableCells::branchBadge(99, 'Apa saja', 'Belum Terdaftar');
        $this->assertStringContainsString('erp-badge--danger', $unknown);
        $this->assertStringContainsString('Belum Terdaftar', $unknown);
        $this->assertStringNotContainsString('Apa saja', $unknown);
        $this->assertStringNotContainsString('badge-light', $unknown);
    }

    public function test_gender_badge_and_view_menu(): void
    {
        $this->assertStringContainsString('Laki-laki', DataTableCells::genderBadge('male'));
        $this->assertStringContainsString('Perempuan', DataTableCells::genderBadge('female'));
        $this->assertStringContainsString('erp-badge--danger', DataTableCells::genderBadge(null));

        $menu = DataTableCells::viewMenu('/pos/9/show');
        $this->assertStringContainsString('href="/pos/9/show"', $menu);
        $this->assertStringContainsString('Lihat detail', $menu);
    }
}
