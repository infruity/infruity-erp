<?php

namespace Tests\Feature;

use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class FlashToastTest extends TestCase
{
    public function test_success_flash_is_an_overlay_toast_not_an_inline_block(): void
    {
        $html = view('template.notif', ['errors' => new ViewErrorBag()])
            ->with('__env', app('view'))
            ->render();
        $this->assertSame('', trim($html), 'Nothing is rendered without a flash message.');

        session()->flash('success', 'Tersimpan.');
        $html = view('template.notif', ['errors' => new ViewErrorBag()])->render();

        $this->assertStringContainsString('toast: true', $html);
        $this->assertStringContainsString('timer', $html);
        $this->assertStringContainsString('Tersimpan.', $html);
        // The inline notice is only the no-JavaScript fallback.
        $this->assertSame(1, substr_count($html, 'alert alert-success'));
        $this->assertStringContainsString('<noscript>', $html);
    }
}
