<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

/** <x-erp.field>: one label / required star / hint / help / error wrapper for every form field. */
final class FieldComponentTest extends TestCase
{
    public function test_it_draws_the_label_with_the_required_star_around_the_control(): void
    {
        $html = Blade::render('<x-erp.field label="Nama Satuan" for="unit-name" required><input id="unit-name" name="name"></x-erp.field>');

        $this->assertStringContainsString('<label for="unit-name" class="erp-field__label">Nama Satuan<span class="erp-field__req" aria-hidden="true">*</span></label>', $html);
        $this->assertStringContainsString('<input id="unit-name" name="name">', $html, 'the control is untouched');
        $this->assertStringNotContainsString('erp-field__error', $html);
    }

    public function test_an_optional_field_has_no_star_and_can_carry_a_hint_and_help(): void
    {
        $html = Blade::render('<x-erp.field label="Catatan" for="note" hint="Opsional" help="Maksimal 255 karakter"><textarea id="note"></textarea></x-erp.field>');

        $this->assertStringNotContainsString('erp-field__req', $html);
        $this->assertStringContainsString('<span class="erp-field__hint">Opsional</span>', $html);
        $this->assertStringContainsString('<p class="erp-field__help">Maksimal 255 karakter</p>', $html);
    }

    public function test_the_validation_error_comes_from_the_bag_by_field_name_or_from_the_error_prop(): void
    {
        $bag = new ViewErrorBag();
        $bag->put('default', new MessageBag(['email' => ['Format e-mail tidak valid.']]));

        View::share('errors', $bag); // what the ShareErrorsFromSession middleware does on a request
        $fromBag = Blade::render('<x-erp.field label="E-mail" for="email"><input id="email"></x-erp.field>');
        $other = Blade::render('<x-erp.field label="Telepon" for="phone"><input id="phone"></x-erp.field>');
        $given = Blade::render('<x-erp.field label="Kode" for="code" error="Kode sudah dipakai."><input id="code"></x-erp.field>');

        $this->assertStringContainsString('<p class="erp-field__error" role="alert">Format e-mail tidak valid.</p>', $fromBag);
        $this->assertStringNotContainsString('erp-field__error', $other, 'only the field that failed shows an error');
        $this->assertStringContainsString('Kode sudah dipakai.', $given);
    }

    public function test_label_text_and_errors_are_escaped(): void
    {
        $html = Blade::render('<x-erp.field :label="$label" for="x" :error="$error"><input id="x"></x-erp.field>', ['label' => '<b>Nama</b>', 'error' => '<script>x</script>']);

        $this->assertStringNotContainsString('<b>Nama</b>', $html);
        $this->assertStringNotContainsString('<script>', $html);
    }

    public function test_the_stylesheet_has_the_sizes_the_tailwind_forms_already_used(): void
    {
        $css = file_get_contents(public_path('assets/css/erp-fields.css'));

        // text-xs font-semibold text-gray-600 mb-1.5  ==  12px / 600 / #4b5563 / 6px
        $this->assertMatchesRegularExpression('/\.erp-field__head \{[^}]*margin-bottom: 6px;/', $css);
        $this->assertMatchesRegularExpression('/\.erp-field__label \{[^}]*color: #4b5563; font-size: 12px; font-weight: 600;/', $css);
        $this->assertMatchesRegularExpression('/\.erp-field__error \{[^}]*font-size: 12px;/', $css);
        $this->assertStringContainsString('.erp-field__req { margin-left: 3px; color: var(--erp-danger', $css);
    }

    public function test_the_customer_and_unit_forms_use_the_component_for_their_plain_fields(): void
    {
        $customer = file_get_contents(base_path('Modules/Master/Resources/views/customer/form-fields.blade.php'));
        $unit = file_get_contents(base_path('Modules/Master/Resources/views/unit/index.blade.php'));

        foreach (['customer_name', 'phone', 'email', 'gender', 'birth_of_date', 'address'] as $id) {
            $this->assertStringContainsString('for="' . $id . '"', $customer);
        }
        $this->assertSame(6, substr_count($customer, '<x-erp.field '));
        $this->assertSame(2, substr_count($unit, '<x-erp.field '));
        // the per-field label markup and the hand-written @error blocks of those fields are gone
        $this->assertStringNotContainsString('class="text-xs font-semibold text-gray-600 mb-1.5 block">Nama Pelanggan', $customer);
        $this->assertStringNotContainsString("@error('email')", $customer);
    }
}
