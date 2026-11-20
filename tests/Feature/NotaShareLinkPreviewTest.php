<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class NotaShareLinkPreviewTest extends TestCase
{
    public function test_the_nota_picture_upload_route_exists_and_needs_a_login(): void
    {
        $this->assertTrue(Route::has('pos.nota-share'));
        $this->assertContains('auth', Route::getRoutes()->getByName('pos.nota-share')->gatherMiddleware());
    }

    public function test_the_public_nota_page_uses_the_stored_picture_as_its_link_preview(): void
    {
        $view = file_get_contents(base_path('Modules/Pos/Resources/views/pos/print2.blade.php'));

        $this->assertStringContainsString("public_path('nota-share/'", $view);
        $this->assertStringContainsString('<meta property="og:image" content="{{ $notaShareUrl ??', $view);
        $this->assertStringContainsString('@unless ($notaShareUrl)', $view);
    }

    public function test_generated_nota_pictures_are_not_committed(): void
    {
        $ignore = file_get_contents(base_path('public/nota-share/.gitignore'));

        $this->assertStringContainsString('*', $ignore);
        $this->assertStringContainsString('!.gitignore', $ignore);
    }
}
