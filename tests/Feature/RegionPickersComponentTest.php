<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class RegionPickersComponentTest extends TestCase
{
    public function test_the_customer_form_uses_the_reference_style_pickers(): void
    {
        $form = file_get_contents(base_path('Modules/Master/Resources/views/customer/form-fields.blade.php'));

        $this->assertStringContainsString('<x-erp.region-pickers :values="$regionValues" :names="$regionNames" />', $form);
        $this->assertStringNotContainsString('data-region-picker=', $form);
        $this->assertStringNotContainsString('role="combobox"', $form);
    }

    public function test_pickers_read_pilih_until_chosen_search_says_cari_and_values_are_posted(): void
    {
        $html = Blade::render('<x-erp.region-pickers :values="[\'province\' => 35]" :names="[\'province\' => \'JAWA TIMUR\']" />');

        foreach (['province', 'city', 'district', 'village'] as $key) {
            $this->assertStringContainsString('<input type="hidden" name="'.$key.'" :value="values[\''.$key.'\']">', $html, $key);
        }
        $this->assertStringContainsString("'Pilih Provinsi'", $html);
        $this->assertStringContainsString("'Pilih Kabupaten / Kota'", $html);
        $this->assertSame(4, substr_count($html, 'placeholder="Cari..."'));
        $this->assertStringContainsString('JAWA TIMUR', $html, 'the saved province name is shown');
    }
}
