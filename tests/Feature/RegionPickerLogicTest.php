<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * The customer form and the master drawers (Pemasok, Cabang, ...) are two implementations of the same rule:
 * Provinsi -> Kabupaten / Kota -> Kecamatan -> Kelurahan, each level locked until the one above has a value,
 * and choosing or changing a level empties the levels below it.
 */
class RegionPickerLogicTest extends TestCase
{
    public function test_the_drawer_and_the_customer_form_use_the_same_chain(): void
    {
        $drawer = file_get_contents(base_path('Modules/Master/Resources/views/partials/mobile-index.blade.php'));
        $customer = file_get_contents(base_path('Modules/Master/Resources/views/customer/js-create.blade.php'));

        $this->assertStringContainsString("({ city: 'province', district: 'city', village: 'district' })[key]", $drawer);
        $this->assertStringContainsString("({ province: ['city', 'district', 'village'], city: ['district', 'village'], district: ['village'] })[key]", $drawer);
        $this->assertStringContainsString("const dependencies = { city: 'province', district: 'city', village: 'district' };", $customer);
        $this->assertStringContainsString("const children = { province: ['city', 'district', 'village'], city: ['district', 'village'], district: ['village'] };", $customer);
    }

    public function test_every_level_below_province_is_locked_in_both_implementations(): void
    {
        $drawer = file_get_contents(base_path('Modules/Master/Resources/views/partials/mobile-index.blade.php'));
        $customer = file_get_contents(base_path('Modules/Master/Resources/views/customer/js-create.blade.php'));
        $form = file_get_contents(base_path('Modules/Master/Resources/views/customer/form-fields.blade.php'));

        $this->assertStringContainsString(":disabled=\"field.key === 'city' ? !form.province : field.key === 'district' ? !form.city : field.key === 'village' ? !form.district : false\"", $drawer);
        $this->assertStringContainsString('if (parent && !this.form[parent]) return;', $drawer, 'a locked picker cannot be opened');
        $this->assertStringContainsString('input.disabled = locked;', $customer);
        $this->assertStringContainsString('disabled:bg-gray-50 disabled:text-gray-400', $drawer);
        $this->assertStringContainsString('disabled:bg-gray-50 disabled:text-gray-400', $form);
    }

    public function test_no_other_view_builds_its_own_region_picker(): void
    {
        $owners = [];
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(base_path('Modules'), \FilesystemIterator::SKIP_DOTS)) as $file) {
            if (str_ends_with($file->getFilename(), '.blade.php') && str_contains($file->getPathname(), '/Resources/views/')
                && preg_match("/ajax\\.province|regionOptions|data-region-picker/", file_get_contents($file->getPathname()))) {
                $owners[] = str_replace(base_path() . '/', '', $file->getPathname());
            }
        }
        sort($owners);

        $this->assertSame([
            'Modules/Master/Resources/views/customer/form-fields.blade.php',
            'Modules/Master/Resources/views/customer/js-create.blade.php',
            'Modules/Master/Resources/views/partials/mobile-index.blade.php',
        ], $owners, 'a new region picker must reuse one of these (or this test must be updated with it)');
    }
}
