<?php

namespace Tests\Feature;

use App\Support\StatusBadge;
use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/** One status pill for the whole ERP: <x-erp.badge> in views, StatusBadge in the HTML that controllers build. */
final class StatusBadgeTest extends TestCase
{
    #[DataProvider('statuses')]
    public function test_a_status_word_has_one_tone(string $status, string $tone): void
    {
        $this->assertSame($tone, StatusBadge::tone($status));
        $this->assertSame($tone, StatusBadge::tone(strtoupper("  {$status} ")), 'case and spaces do not matter');
    }

    public static function statuses(): array
    {
        return [
            'selesai' => ['selesai', 'success'], 'paid' => ['paid', 'success'], 'lunas' => ['lunas', 'success'], 'posting' => ['posting', 'success'],
            'proses' => ['proses', 'warning'], 'debt' => ['debt', 'warning'],
            'draft' => ['draft', 'danger'], 'temp' => ['temp', 'danger'], 'canceled' => ['canceled', 'danger'], 'batal' => ['batal', 'danger'],
            'dikirim' => ['dikirim', 'info'],
            'pending' => ['pending', 'neutral'],
            'unknown word' => ['sesuatu', 'neutral'],
        ];
    }

    public function test_an_empty_status_is_neutral(): void
    {
        $this->assertSame('neutral', StatusBadge::tone(null));
        $this->assertSame('neutral', StatusBadge::tone(''));
    }

    public function test_the_html_has_the_shared_classes_and_escapes_the_label(): void
    {
        $this->assertSame('<span class="erp-badge erp-badge--success">Selesai</span>', StatusBadge::for('selesai'));
        $this->assertSame('<span class="erp-badge erp-badge--danger">Draft</span>', StatusBadge::for('temp', 'Draft'));
        $this->assertSame('<span class="erp-badge erp-badge--primary ms-2">Toko A</span>', StatusBadge::html('Toko A', 'primary', 'ms-2'));
        $this->assertStringContainsString('&lt;script&gt;', StatusBadge::for('<script>alert(1)</script>'), 'a status from the database is never printed as markup');
        $this->assertStringContainsString('erp-badge--neutral', StatusBadge::html('x', 'not-a-tone'));
    }

    public function test_the_component_takes_a_status_or_a_tone(): void
    {
        $byStatus = Blade::render('<x-erp.badge status="selesai">Selesai</x-erp.badge>');
        $byTone = Blade::render('<x-erp.badge tone="danger" icon="ph-lock-simple" class="ms-1">Locked</x-erp.badge>');
        $plain = Blade::render('<x-erp.badge>Baru</x-erp.badge>');

        $this->assertStringContainsString('erp-badge erp-badge--success', $byStatus);
        $this->assertStringContainsString('erp-badge erp-badge--danger ms-1', $byTone);
        $this->assertStringContainsString('<i class="ph ph-lock-simple"', $byTone);
        $this->assertStringContainsString('erp-badge--neutral', $plain);
    }

    public function test_the_stylesheet_has_every_tone_on_the_erp_tokens(): void
    {
        $css = file_get_contents(public_path('assets/css/erp-fields.css'));

        foreach (StatusBadge::TONES as $tone) {
            $this->assertStringContainsString(".erp-badge--{$tone} {", $css, $tone);
        }
        $this->assertStringContainsString('var(--erp-success-soft', $css);
        $this->assertStringContainsString('var(--erp-danger-soft', $css);
        // Same shape as the legacy pills (erp-theme.css): padding 5px 9px, 11px, 600, fully round.
        $this->assertMatchesRegularExpression('/\.erp-badge \{[^}]*padding: 5px 9px; border-radius: 999px;[^}]*font-size: 11px; font-weight: 600;/', $css);
        $this->assertStringContainsString('.erp-legacy .badge.badge-light-secondary { background-color: #f3f4f6; color: #4b5563; }', $css, 'legacy secondary pill = neutral tone');
        $this->assertStringContainsString('.erp-legacy .badge { border-radius: 999px; padding: 5px 9px; font-size: 11px; font-weight: 600;', file_get_contents(public_path('assets/css/erp-theme.css')));
    }

    public function test_the_transfer_list_draws_its_status_with_the_helper(): void
    {
        $controller = file_get_contents(base_path('Modules/Transaction/Http/Controllers/TransferController.php'));

        $this->assertStringContainsString('StatusBadge::for($item->status', $controller);
        $this->assertStringNotContainsString('$statusBadges', $controller, 'the per-controller status map is gone');
    }
}
