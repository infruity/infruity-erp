<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Every field of the ERP lights up like the search field when it is active: green border, white background, soft green
 * ring (public/assets/css/erp-fields.css). Before, Metronic's grey/blue focus, a dark-teal outline from erp-theme.css and
 * a few different greens made each form look different.
 */
final class FieldActiveStateContractTest extends TestCase
{
    private function css(string $name): string
    {
        return file_get_contents(public_path("assets/css/{$name}.css"));
    }

    public function test_the_shared_active_state_is_the_one_of_the_search_field(): void
    {
        $search = $this->css('erp-mobile-search');
        $fields = $this->css('erp-fields');

        $this->assertMatchesRegularExpression('/\.erp-mobile-search:focus-within \{[^}]*border-color:#34a684[^}]*0 0 0 2px rgba\(16,185,129,\.13\)/', $search);
        $this->assertStringContainsString('--erp-active-border: #34a684;', $fields);
        $this->assertStringContainsString('--erp-active-ring: 0 0 0 2px rgba(16, 185, 129, .13);', $fields);
        $this->assertStringContainsString('--erp-active-bg: #fff;', $fields);
    }

    public function test_both_layouts_load_the_shared_stylesheet_after_the_theme(): void
    {
        foreach (['resources/views/template/root.blade.php', 'resources/views/layouts/erp-tailwind.blade.php'] as $layout) {
            $source = file_get_contents(base_path($layout));
            $this->assertStringContainsString('assets/css/erp-fields.css', $layout . $source);
            $this->assertGreaterThan(strpos($source, 'assets/css/erp-theme.css'), strpos($source, 'assets/css/erp-fields.css'), "{$layout}: erp-fields.css must come after erp-theme.css");
        }
    }

    public function test_it_covers_metronic_fields_select2_pickers_and_tailwind_fields(): void
    {
        $fields = $this->css('erp-fields');

        foreach (['.form-control', '.form-select', '.select2-container--open .select2-selection', '.erp-picker__trigger[aria-expanded="true"]', '.erp-picker__search input:focus', '[class*="focus:border-"]'] as $selector) {
            $this->assertStringContainsString($selector, $fields, $selector);
        }
        $this->assertStringContainsString('border-color: var(--erp-active-border) !important;', $fields, 'beats borders set inline on a field');
    }

    public function test_text_fields_no_longer_get_the_dark_teal_outline(): void
    {
        $theme = $this->css('erp-theme');

        $this->assertStringNotContainsString(':is(button,a,input,select,textarea,[tabindex]):focus-visible', $theme);
        $this->assertMatchesRegularExpression('/\.erp-ui :is\(button,a,\[tabindex\]\):focus-visible \{\s*outline: 2px solid var\(--erp-primary\);/', $theme, 'buttons and links keep their outline');
        $this->assertMatchesRegularExpression('/\.erp-ui :is\(input,select,textarea\):focus-visible \{\s*outline: 2px solid rgba\(16, 185, 129, \.13\);/', $theme);
    }

    public function test_page_stylesheets_use_the_shared_tokens_for_field_focus(): void
    {
        $legacy = [
            'pos-form' => ['box-shadow:0 0 0 2px rgba(11,89,91,.12)', 'box-shadow:0 0 0 1px #10b981'],
            'production-form-reference' => ['box-shadow:0 0 0 1px #10b981', 'border-color:#10b981'],
            'sortir-mobile' => ['.sortir-search:focus-within { border-color:#10b981; }'],
            'transfer-mobile' => ['.transfer-search:focus-within { border-color:#10b981; }'],
            'erp-picker' => ['rgba(11,89,91,.1)'],
            'erp-nota-settings' => ['.nota-field :is(input,textarea):focus-visible,.nota-branch-picker select:focus-visible { outline:2px solid'],
        ];

        foreach ($legacy as $file => $snippets) {
            $css = $this->css($file);
            foreach ($snippets as $snippet) {
                $this->assertStringNotContainsString($snippet, $css, "{$file}.css still has its own field focus colour: {$snippet}");
            }
        }
        $this->assertStringContainsString('var(--erp-active-border)', $this->css('pos-form'));
        $this->assertStringContainsString('var(--erp-active-border)', $this->css('production-form-reference'));
    }

    public function test_the_add_stock_opname_form_is_a_full_page_on_a_phone_and_a_sheet_on_desktop(): void
    {
        $css = $this->css('stock-opname-reference');
        $view = file_get_contents(base_path('Modules/Transaction/Resources/views/stock-opname/index.blade.php'));

        $this->assertMatchesRegularExpression('/@media \(max-width: 767\.98px\) \{[^@]*#kt_modal_add_customer \.modal-dialog \{[^}]*position: fixed; inset: 0;[^}]*height: 100dvh;/s', $css);
        $this->assertMatchesRegularExpression('/#kt_modal_add_customer \.modal-content \{[^}]*height: 100dvh;[^}]*border-radius: 0 !important;/', $css);
        $this->assertStringContainsString('#kt_modal_add_customer .modal-body { flex: 1 1 auto; min-height: 0; overflow-y: auto;', $css);
        $this->assertStringContainsString('env(safe-area-inset-bottom)', $css, 'the actions clear the home indicator');

        // Back arrow on a phone, the cross on desktop; the same button, so the existing close handling keeps working.
        $this->assertStringContainsString('id="kt_modal_add_customer_close"', $view);
        $this->assertStringContainsString('ph-arrow-left so-close-back', $view);
        $this->assertStringContainsString('ph-x so-close-x', $view);
        $this->assertStringContainsString('#kt_modal_add_customer .so-close-back { display: none; }', $css);
    }

    public function test_the_theme_lets_sweetalert_buttons_keep_the_colours_the_dialog_asks_for(): void
    {
        $theme = $this->css('erp-theme');

        $this->assertStringContainsString('.erp-ui .swal2-styled.swal2-confirm { background-color: var(--swal2-confirm-button-background-color, var(--erp-primary)); }', $theme);
        $this->assertStringContainsString('.erp-ui .swal2-styled.swal2-cancel { background-color: var(--swal2-cancel-button-background-color, #4b5563); }', $theme);
    }
}
