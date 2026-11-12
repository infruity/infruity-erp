<?php

namespace Tests\Feature;

use Tests\TestCase;

class CustomerDetailReferenceTest extends TestCase
{
    public function test_customer_detail_has_the_reference_rows(): void
    {
        $view = file_get_contents(base_path('Modules/Master/Resources/views/partials/mobile-index.blade.php'));

        $this->assertStringContainsString("@elseif (\$routeBase === 'customers')", $view);
        foreach (['ph-user', 'ph-calendar-blank', 'ph-phone', 'ph-map-pin', 'ph-clock'] as $icon) {
            $this->assertStringContainsString('<i class="ph-duotone '.$icon.' text-lg">', $view, $icon);
        }
        foreach (['Usia Pelanggan', 'Nomor WhatsApp (Klik untuk Chat)', 'Lokasi Domisili (Klik untuk Buka Google Maps)', "'Memperbarui pada ' + formatDetailDate("] as $text) {
            $this->assertStringContainsString($text, $view, $text);
        }
    }

    public function test_customer_list_sends_birth_date_updater_and_city_for_the_detail(): void
    {
        $controller = file_get_contents(base_path('Modules/Master/Http/Controllers/CustomerController.php'));

        $this->assertStringContainsString("'editor.nm_user as updated_by_name', 'regency.name as city_name'", $controller);
        $this->assertStringContainsString("'customer.birth_of_date'", $controller);
        $this->assertStringContainsString("'customer.updated_at'", $controller);
    }
}
