<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class FeedbackComponentsTest extends TestCase
{
    public function test_alert_renders_each_type_in_its_colours_with_a_title(): void
    {
        $map = ['info' => 'text-blue-700', 'success' => 'text-emerald-700', 'warning' => 'text-amber-700', 'danger' => 'text-red-700'];

        foreach ($map as $type => $class) {
            $html = Blade::render('<x-erp.alert type="'.$type.'" title="Judul">Isi pesan</x-erp.alert>');
            $this->assertStringContainsString($class, $html);
            $this->assertStringContainsString('Judul', $html);
            $this->assertStringContainsString('Isi pesan', $html);
            $this->assertStringNotContainsString('alert-', $html, 'no Bootstrap alert classes');
        }
    }

    public function test_alert_is_announced_as_alert_for_problems_and_dismissible_on_request(): void
    {
        $this->assertStringContainsString('role="alert"', Blade::render('<x-erp.alert type="danger">x</x-erp.alert>'));
        $this->assertStringContainsString('role="status"', Blade::render('<x-erp.alert>x</x-erp.alert>'));
        $this->assertStringContainsString('x-show="shown"', Blade::render('<x-erp.alert dismissible>x</x-erp.alert>'));
        $this->assertStringNotContainsString('x-show', Blade::render('<x-erp.alert>x</x-erp.alert>'));
    }

    public function test_spinner_and_loading_use_the_tailwind_spinner(): void
    {
        $spinner = Blade::render('<x-erp.spinner size="lg" />');
        $this->assertStringContainsString('animate-spin', $spinner);
        $this->assertStringNotContainsString('spinner-border', $spinner);

        $loading = Blade::render('<x-erp.loading>Menyimpan…</x-erp.loading>');
        $this->assertStringContainsString('animate-spin', $loading);
        $this->assertStringContainsString('Menyimpan…', $loading);
        $this->assertStringContainsString('Memuat…', Blade::render('<x-erp.loading />'));
        $this->assertStringContainsString('absolute inset-0', Blade::render('<x-erp.loading overlay />'));
    }

    public function test_legacy_status_colours_are_themed(): void
    {
        $css = file_get_contents(public_path('assets/css/erp-theme.css'));

        foreach (['.btn.btn-danger', '.btn.btn-success', '.btn.btn-warning', '.btn.btn-light-success', '.btn.btn-light-danger', '.badge.badge-light-success', '.badge.badge-light-danger', '.badge.badge-light-warning', '.indicator-progress', '.swal2-toast'] as $selector) {
            $this->assertStringContainsString($selector, $css, "$selector must be themed");
        }
    }
}
