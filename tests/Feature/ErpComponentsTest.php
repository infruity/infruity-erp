<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/**
 * Render komponen x-erp.* (migrasi Bootstrap -> Tailwind, B1).
 * Yang dikunci: struktur dan aksesibilitas, bukan tampilan.
 */
class ErpComponentsTest extends TestCase
{
    /**
     * Catatan: di template inline, slot bernama (<x-slot:x>) yang langsung diikuti teks tanpa
     * baris baru membocorkan output buffer (keanehan kompiler Blade). Beri baris baru.
     */
    private function render(string $template, array $data = []): string
    {
        return Blade::render($template, $data);
    }

    public function test_dialog_renders_a_native_dialog_with_labelled_title_and_close_button(): void
    {
        $html = $this->render('<x-erp.dialog id="edit" title="Ubah produk" size="lg">Isi</x-erp.dialog>');

        $this->assertStringContainsString('<dialog id="edit"', $html);
        $this->assertStringContainsString('data-erp-dialog', $html);
        $this->assertStringContainsString('aria-labelledby="edit-title"', $html);
        $this->assertStringContainsString('id="edit-title"', $html);
        $this->assertStringContainsString('erp-dialog--lg', $html);
        $this->assertStringContainsString('data-erp-close', $html);
        $this->assertStringContainsString('aria-label="Tutup"', $html);
        $this->assertStringNotContainsString('data-static', $html);
        $this->assertStringNotContainsString('erp-dialog__footer', $html);
    }

    public function test_dialog_static_variant_and_footer(): void
    {
        $html = $this->render("<x-erp.dialog id=\"x\" title=\"T\" static variant=\"sheet\">\n<x-slot:footer>Simpan</x-slot:footer>\nIsi\n</x-erp.dialog>");

        $this->assertStringContainsString('data-static', $html);
        $this->assertStringContainsString('erp-dialog--sheet', $html);
        $this->assertStringContainsString('erp-dialog__footer', $html);
        $this->assertStringContainsString('Simpan', $html);
    }

    public function test_dialog_ignores_unknown_size_and_escapes_the_title(): void
    {
        $html = $this->render('<x-erp.dialog id="x" :title="$t" size="bogus">a</x-erp.dialog>', ['t' => '<b>x</b>']);

        $this->assertStringContainsString('erp-dialog--md', $html);
        $this->assertStringNotContainsString('<b>x</b>', $html);
        $this->assertStringContainsString('&lt;b&gt;x&lt;/b&gt;', $html);
    }

    public function test_drawer_is_a_dialog_with_the_drawer_variant(): void
    {
        $html = $this->render('<x-erp.drawer id="side" title="Detail">Isi</x-erp.drawer>');

        $this->assertStringContainsString('<dialog id="side"', $html);
        $this->assertStringContainsString('erp-dialog--drawer', $html);
    }

    public function test_badge_maps_tone_to_a_known_class_and_falls_back_to_neutral(): void
    {
        $this->assertStringContainsString('erp-badge--success', $this->render('<x-erp.badge tone="success">Lunas</x-erp.badge>'));
        $this->assertStringContainsString('erp-badge--neutral', $this->render('<x-erp.badge tone="nonsense">x</x-erp.badge>'));
    }

    public function test_badge_escapes_its_content_when_rendered_from_a_controller(): void
    {
        $html = view('components.erp.badge', ['tone' => 'danger', 'slot' => '<script>alert(1)</script>'])->render();

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function test_avatar_shows_initials_picks_a_stable_tone_and_supports_images(): void
    {
        $a = $this->render('<x-erp.avatar name="Budi Santoso" />');
        $b = $this->render('<x-erp.avatar name="Budi Santoso" />');

        $this->assertStringContainsString('>BS<', preg_replace('/\s+/', '', str_replace('> <', '><', $a)) ?: $a);
        $this->assertSame($a, $b, 'warna harus stabil untuk nama yang sama');
        $this->assertMatchesRegularExpression('/erp-avatar--(success|warning|danger|info|primary|purple)/', $a);
        $this->assertStringContainsString('<img src="/x.png"', $this->render('<x-erp.avatar name="A" src="/x.png" />'));
        $this->assertStringContainsString('?', $this->render('<x-erp.avatar />'));
    }

    public function test_cell_title_escapes_user_data(): void
    {
        $html = view('components.erp.cell-title', ['title' => '<img src=x onerror=alert(1)>', 'subtitle' => '"><svg onload=1>'])->render();

        $this->assertStringNotContainsString('<img', $html);
        $this->assertStringNotContainsString('<svg', $html);
        $this->assertStringContainsString('erp-cell-title__sub', $html);
    }

    public function test_cell_title_omits_empty_subtitle_and_links_when_href_given(): void
    {
        $plain = view('components.erp.cell-title', ['title' => 'Apel'])->render();
        $linked = view('components.erp.cell-title', ['title' => 'Apel', 'href' => '/p/1'])->render();

        $this->assertStringNotContainsString('erp-cell-title__sub', $plain);
        $this->assertStringContainsString('<a href="/p/1"', $linked);
    }

    public function test_row_actions_hides_items_without_access_and_renders_nothing_when_all_hidden(): void
    {
        $some = view('components.erp.row-actions', ['label' => 'Aksi Apel', 'actions' => [
            ['label' => 'Ubah', 'icon' => 'pencil-simple', 'onclick' => 'editProduct(7)', 'can' => true],
            ['label' => 'Hapus', 'icon' => 'trash', 'tone' => 'danger', 'onclick' => 'deleteProduct(7)', 'can' => false],
        ]])->render();

        $this->assertStringContainsString('onclick="editProduct(7)"', $some);
        $this->assertStringContainsString('ph-pencil-simple', $some);
        $this->assertStringNotContainsString('deleteProduct', $some);
        $this->assertStringContainsString('aria-label="Aksi Apel"', $some);
        $this->assertStringContainsString('role="menuitem"', $some);

        $none = view('components.erp.row-actions', ['actions' => [['label' => 'Hapus', 'can' => false]]])->render();
        $this->assertSame('', trim($none));
    }

    public function test_row_actions_marks_danger_items_and_supports_links_and_data_attributes(): void
    {
        $html = view('components.erp.row-actions', ['actions' => [
            ['label' => 'Lihat', 'href' => '/products/7', 'icon' => 'eye'],
            ['label' => 'Hapus', 'tone' => 'danger', 'data' => ['id' => 7], 'onclick' => 'del(7)'],
        ]])->render();

        $this->assertStringContainsString('<a role="menuitem" href="/products/7"', $html);
        $this->assertStringContainsString('erp-menu__item--danger', $html);
        $this->assertStringContainsString('data-id="7"', $html);
    }

    public function test_row_actions_escapes_labels_and_attributes(): void
    {
        $html = view('components.erp.row-actions', ['label' => '"><x>', 'actions' => [
            ['label' => '<b>Hapus</b>', 'onclick' => 'a("b")'],
        ]])->render();

        $this->assertStringNotContainsString('<b>Hapus</b>', $html);
        $this->assertStringNotContainsString('"><x>', $html);
    }

    public function test_dropdown_has_menu_aria_and_teleports_its_panel(): void
    {
        $html = $this->render('<x-erp.dropdown label="Aksi"><x-slot:trigger>...</x-slot:trigger><button role="menuitem">A</button></x-erp.dropdown>');

        $this->assertStringContainsString('aria-haspopup="menu"', $html);
        $this->assertStringContainsString('aria-label="Aksi"', $html);
        $this->assertStringContainsString('x-teleport="body"', $html);
        $this->assertStringContainsString('role="menu"', $html);
        $this->assertStringContainsString('x-anchor.bottom-end', $html);
    }

    public function test_dropdown_can_align_to_the_start(): void
    {
        $html = $this->render("<x-erp.dropdown align=\"start\">\n<x-slot:trigger>x</x-slot:trigger>\ny\n</x-erp.dropdown>");

        $this->assertStringContainsString('x-anchor.bottom-start', $html);
    }

    public function test_tabs_expose_tablist_semantics_and_panels_link_back(): void
    {
        $html = $this->render(<<<'BLADE'
            <x-erp.tabs :tabs="[['name' => 'a', 'label' => 'Umum'], ['name' => 'b', 'label' => 'Harga']]" active="b">
                <x-erp.tab-panel name="a">A</x-erp.tab-panel>
                <x-erp.tab-panel name="b">B</x-erp.tab-panel>
            </x-erp.tabs>
        BLADE);

        $this->assertStringContainsString('role="tablist"', $html);
        $this->assertSame(2, substr_count($html, 'role="tab"'));
        $this->assertStringContainsString('aria-controls="panel-a"', $html);
        $this->assertStringContainsString('role="tabpanel" id="panel-b" aria-labelledby="tab-b"', $html);
        $this->assertStringContainsString('active: "b"', str_replace(['&quot;', "'"], '"', $html));
    }

    public function test_collapse_wires_aria_expanded_to_its_panel(): void
    {
        $html = $this->render('<x-erp.collapse title="Rincian" open>Isi</x-erp.collapse>');

        $this->assertMatchesRegularExpression('/aria-controls="(erp-collapse-[A-Za-z0-9]+)"/', $html);
        preg_match('/aria-controls="([^"]+)"/', $html, $m);
        $this->assertStringContainsString('id="'.$m[1].'"', $html);
        $this->assertStringContainsString('x-collapse', $html);
        $this->assertStringContainsString(':aria-expanded="open.toString()"', $html);
    }

    public function test_check_all_targets_a_container_and_is_labelled(): void
    {
        $html = $this->render('<x-erp.check-all target="#tbl" />');

        $this->assertStringContainsString('data-erp-check-all="#tbl"', $html);
        $this->assertStringContainsString('aria-label="Pilih semua"', $html);
        $this->assertStringContainsString('erp-check', $html);
    }

    public function test_tooltip_links_the_trigger_to_a_role_tooltip(): void
    {
        $html = $this->render('<x-erp.tooltip text="Bantuan"><button>?</button></x-erp.tooltip>');

        preg_match('/aria-describedby="([^"]+)"/', $html, $m);
        $this->assertNotEmpty($m);
        $this->assertStringContainsString('id="'.$m[1].'"', $html);
        $this->assertStringContainsString('role="tooltip"', $html);
        $this->assertStringContainsString('Bantuan', $html);
    }

    public function test_panel_renders_header_only_when_there_is_a_title_or_toolbar(): void
    {
        $bare = $this->render('<x-erp.panel>Isi</x-erp.panel>');
        $titled = $this->render("<x-erp.panel title=\"Ringkasan\">\n<x-slot:footer>F</x-slot:footer>\nIsi\n</x-erp.panel>");

        $this->assertStringNotContainsString('erp-panel__header', $bare);
        $this->assertStringContainsString('erp-panel__header', $titled);
        $this->assertStringContainsString('erp-panel__footer', $titled);
    }
}
