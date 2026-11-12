<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Sizes of the mobile dashboard, taken from the HTML reference (infruity-frontend 003. ERP/001. Dashboard) and measured in
 * the browser: the headers sit directly on their sub-headers and the icon buttons are the small ones of the reference.
 */
final class DashboardReferenceSpacingTest extends TestCase
{
    private function source(): string
    {
        return file_get_contents(base_path('Modules/Crm/Resources/views/dashboard/index.blade.php'));
    }

    public function test_the_contributor_card_header_is_13_5px_over_a_10_5px_sub_header_with_no_gap(): void
    {
        $view = $this->source();

        $this->assertStringContainsString('<h2 class="text-[13.5px] font-bold tracking-tight text-gray-900">', $view);
        $this->assertStringContainsString('<p class="text-[10.5px] text-gray-400" x-text="contributorSubtitle"></p>', $view);
        $this->assertStringContainsString('<div class="mb-2 flex items-center justify-between gap-3">', $view, 'header row to list: 8px');
        $this->assertStringNotContainsString('class="mt-1 text-[11px] text-slate-400" x-text="contributorSubtitle"', $view);
    }

    public function test_the_card_is_the_reference_card(): void
    {
        $view = $this->source();

        $this->assertStringContainsString('mt-[18px] mb-4 min-h-[248px] shrink-0 rounded-2xl border border-gray-100 bg-white p-4 shadow-[0_2px_12px_rgba(0,0,0,0.04)]', $view);
        $this->assertStringContainsString('flex items-center justify-between gap-2 py-3 first:pt-1 last:pb-1', $view);
        $this->assertStringContainsString('w-9 h-9 rounded-xl border flex items-center justify-center text-[20px]', $view, 'row icon tile: 36px');
    }

    public function test_the_icon_buttons_are_28px_with_14px_and_16px_icons(): void
    {
        $view = $this->source();

        // sort toggle of the contributor card: w-7 h-7, icon text-sm (14px)
        $this->assertMatchesRegularExpression('/class="h-7 w-7 shrink-0 rounded-full border[^"]*" :class="contributorButtonClass"/', $view);
        $this->assertStringContainsString('<i class="ph-bold text-sm" :class="showBottom', $view);
        // filter button of the hero: w-7 h-7, icon 16px
        $this->assertStringContainsString('class="h-7 w-7 rounded-full border border-white/15 bg-white/10 flex items-center justify-center hover:bg-white/20"><i class="ph ph-sliders-horizontal text-[16px]"></i>', $view);
        $this->assertStringNotContainsString('h-10 w-10 shrink-0 rounded-full border', $view);
        $this->assertStringNotContainsString('h-9 w-9 rounded-full border border-white/15', $view);
    }

    public function test_the_hero_labels_follow_the_reference_gaps(): void
    {
        $view = $this->source();

        $this->assertStringContainsString('<p class="mb-1.5 text-[11.5px] font-medium"', $view, 'label to amount: 6px');
        $this->assertStringContainsString('<div class="mt-[18px] flex-1 border-t border-white/15 pt-3.5 flex flex-col">', $view);
        $this->assertStringContainsString('<p class="text-[10px]" :style="\'color:\' + modePalette.subtext" x-text="chartSubtitle"></p>', $view, 'chart title sits on its sub-title');
        $this->assertStringContainsString('text-[9.5px]" style="color:rgba(209,250,229,.7)"', $view);
        $this->assertStringContainsString('text-[10.5px] font-bold leading-none', $view, 'day name');
        $this->assertStringContainsString('mt-0.5 text-[8.5px]', $view, 'day number');
    }
}
