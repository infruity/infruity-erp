<?php

namespace Tests\Feature;

use Tests\TestCase;

final class ProductionDetailUiContractTest extends TestCase
{
    private function show(): string
    {
        return file_get_contents(base_path('Modules/Transaction/Resources/views/production/show.blade.php'));
    }

    public function test_detail_is_a_header_meta_ingredients_and_cost_layout(): void
    {
        $show = $this->show();

        foreach (['production-detail', 'production-detail__meta', 'production-detail__ingredient', 'production-detail__total', 'Bahan Baku yang Digunakan', 'Total Biaya Produksi', 'HPP per Unit'] as $contract) {
            $this->assertStringContainsString($contract, $show, "Hilang: {$contract}");
        }
        $this->assertStringNotContainsString('erp-detail-header collapsible', $show);
        $this->assertStringNotContainsString('table-row-dashed', $show);
    }

    public function test_actions_follow_the_same_status_rules(): void
    {
        $show = $this->show();

        $this->assertStringContainsString("in_array(\$data->status, ['temp', 'draft'])", $show);
        $this->assertStringContainsString("\$data->status == 'draft'", $show);
        foreach (["route('production.edit', \$data->id)", "route('production.payment', \$data->id)", "route('production.print', \$data->id)", "route('production.index')", "route('pos.show', \$data->pos_id)"] as $link) {
            $this->assertStringContainsString($link, $show, "Hilang: {$link}");
        }
        foreach (['Selesai', 'Siap Produksi', 'Draft Sementara'] as $label) {
            $this->assertStringContainsString($label, $show);
        }
    }

    public function test_cost_comes_from_the_server_values_and_frozen_hpp_is_explained(): void
    {
        $show = $this->show();

        $this->assertStringContainsString('$total_hpp', $show);
        $this->assertStringContainsString('$hpp_per_unit', $show);
        $this->assertStringContainsString('$production_detail', $show);
        // Posted runs: the table uses today's ingredient prices, the totals use the frozen cost.
        $this->assertStringContainsString('dibekukan', $show);
        // No client-side recalculation of money on this page.
        $this->assertStringNotContainsString('x-data', $show);
    }

    public function test_every_detail_field_is_still_shown(): void
    {
        $show = $this->show();

        foreach (['production_number', 'products->name', 'staff->name', 'description', 'creator->nm_user', 'created_at', 'updated_at', 'production_detail->count()'] as $field) {
            $this->assertStringContainsString($field, $show, "Field hilang: {$field}");
        }
    }

    public function test_completed_pos_productions_show_the_same_done_label_as_the_list(): void
    {
        $detail = file_get_contents(base_path('Modules/Transaction/Resources/views/production/show.blade.php'));

        // POS-linked productions are stored as "complete"; the list renders them as Selesai.
        $this->assertStringContainsString("in_array(\$data->status, ['posting', 'complete'])", $detail);
        $this->assertMatchesRegularExpression("/in_array\\(\\\$data->status, \\['posting', 'complete'\\]\\)\\)\\s*<span class=\"production-detail__badge is-done\">Selesai<\\/span>/", $detail);
    }

    public function test_detail_dates_use_the_recorded_time_like_the_list(): void
    {
        $detail = file_get_contents(base_path('Modules/Transaction/Resources/views/production/show.blade.php'));

        // The list shows created_at; the header and meta row must not mix production_date with its clock time.
        $this->assertStringNotContainsString('dateindo($data->production_date)', $detail);
        $this->assertSame(2, substr_count($detail, 'dateindo($data->created_at)'));
    }
}
