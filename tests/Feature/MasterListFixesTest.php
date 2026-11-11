<?php

namespace Tests\Feature;

use App\Support\Like;
use Tests\TestCase;

final class MasterListFixesTest extends TestCase
{
    private function src(string $path): string
    {
        return file_get_contents(base_path($path));
    }

    public function test_like_helper_escapes_wildcards_and_the_escape_character(): void
    {
        $this->assertSame('%mangga%', Like::contains('mangga'));
        $this->assertSame('%100\\%%', Like::contains('100%'));
        $this->assertSame('%a\\_b%', Like::contains('a_b'));
        $this->assertSame('%a\\\\b%', Like::contains('a\\b'));
        $this->assertSame('%%', Like::contains(''));
    }

    public function test_user_receipt_product_and_role_searches_escape_wildcards(): void
    {
        $this->assertStringContainsString('Like::contains($request->input(\'q\'))', $this->src('app/Http/Controllers/UserController.php'));
        $this->assertStringNotContainsString("'%' . \$request->input('q') . '%'", $this->src('app/Http/Controllers/UserController.php'));

        $receipt = $this->src('Modules/Transaction/Http/Controllers/ProductReceiptController.php');
        $this->assertSame(2, substr_count(substr($receipt, 0, 4000), 'Like::contains($search)'));

        $product = $this->src('Modules/Master/Http/Controllers/ProductController.php');
        $this->assertStringContainsString('$pattern = Like::contains($term);', $product);

        $role = $this->src('app/Http/Controllers/P_role.php');
        $this->assertSame(2, substr_count($role, 'Like::contains($search)'));
    }

    public function test_role_search_matches_the_code_shown_on_the_row(): void
    {
        $role = $this->src('app/Http/Controllers/P_role.php');
        $view = $this->src('resources/views/admin/role/index.blade.php');

        // The row renders ROLE-0003 for id_role 3, so searching that text has to find it.
        $this->assertStringContainsString('ROLE-', $view);
        $this->assertStringContainsString("preg_match('/^role-?0*(\\d+)\$/i'", $role);
    }

    public function test_roles_page_has_a_mobile_search_that_submits_the_same_q_parameter(): void
    {
        $view = $this->src('resources/views/admin/role/index.blade.php');

        $this->assertStringContainsString('<x-erp.mobile-search', $view);
        $this->assertStringContainsString('name="q"', $view);
        $this->assertMatchesRegularExpression('/<form[^>]*method="GET"[^>]*class="md:hidden"|<form[^>]*class="md:hidden"[^>]*method="GET"/', $view);
        $this->assertStringContainsString('aria-label="Cari role"', $view);
    }

    public function test_product_photo_preview_has_no_empty_src_attribute(): void
    {
        $form = $this->src('Modules/Master/Resources/views/products/partials/create-form.blade.php');
        $index = $this->src('Modules/Master/Resources/views/products/index.blade.php');

        $this->assertStringNotContainsString('src="{{ isset($data) && $data->image ? asset(\'storage/\' . $data->image) : \'\' }}"', $form);
        $this->assertStringContainsString("@if(isset(\$data) && \$data->image) src=", $form);
        $this->assertStringNotContainsString("#product-image-preview').src = ''", $index);
        $this->assertStringContainsString("removeAttribute('src')", $index);
    }
}
