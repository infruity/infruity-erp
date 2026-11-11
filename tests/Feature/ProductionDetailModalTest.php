<?php

namespace Tests\Feature;

use Tests\TestCase;

final class ProductionDetailModalTest extends TestCase
{
    public function test_production_list_hosts_the_detail_popup_without_replacing_the_full_page_route(): void
    {
        $list = file_get_contents(base_path('Modules/Transaction/Resources/views/production/index.blade.php'));
        $detail = file_get_contents(base_path('Modules/Transaction/Resources/views/production/show.blade.php'));

        $this->assertStringContainsString('<x-erp.detail-dialog', $list);
        $this->assertStringContainsString('x-teleport="body"', $list);
        $this->assertStringContainsString('x-html="productionDetailHtml"', $list);
        $this->assertStringContainsString('x-show="productionDetailLoading"', $list);
        $this->assertStringContainsString('x-on:keydown.tab="trapProductionDetailFocus($event)"', $list);
        $this->assertStringContainsString('production-detail-reference.css', $list);
        $this->assertStringContainsString('production-list-reference.js', $list);
        $this->assertStringContainsString('createDetailModal', file_get_contents(public_path('assets/js/production-list-reference.js')));
        $this->assertStringContainsString("</x-erp.workspace>\n    </div>\n    <div data-production-detail-modal", $list);
        $this->assertStringContainsString("route('production.index')", $list);

        // Direct URLs and normal navigation fallback keep the complete server-rendered detail page.
        $this->assertStringContainsString("@extends('template.root')", $detail);
        $this->assertStringContainsString('<div class="production-detail">', $detail);
        $this->assertStringContainsString("route('production.index')", $detail);
        $this->assertStringContainsString("route('production.print', \$data->id)", $detail);
        $this->assertStringNotContainsString('{!!', $detail);
    }

    public function test_popup_is_scrollable_on_mobile_and_locks_only_the_list_behind_it(): void
    {
        $css = file_get_contents(public_path('assets/css/production-detail-reference.css'));
        $script = file_get_contents(public_path('assets/js/production-list-reference.js'));
        $detail = file_get_contents(base_path('Modules/Transaction/Resources/views/production/show.blade.php'));
        preg_match('/<style>(.*?)<\/style>/s', $detail, $inlineStyles);
        $pageCss = explode('/* The production detail stays over the current list instead of replacing it. */', $css, 2)[0];
        $pageCss = preg_replace('/\/\*.*?\*\//s', '', $pageCss);
        $normalizeCss = static fn (string $value): string => preg_replace('/\s+/', '', $value);

        $this->assertSame($normalizeCss($inlineStyles[1] ?? ''), $normalizeCss($pageCss));
        $this->assertStringContainsString('#production-detail-modal > section', $css);
        $this->assertStringContainsString('max-height: calc(100dvh - 24px)', $css);
        $this->assertStringContainsString('#kt_content.production-detail-open', $css);
        $this->assertStringContainsString('.production-detail__card {', $css);
        $this->assertStringContainsString("classList.add('production-detail-open')", $script);
        $this->assertStringContainsString('content.inert=true', $script);
        $this->assertStringContainsString('infruityProductionDetailModal', $script);
        $this->assertStringContainsString('sanitizeDetailElement(detail,targetUrl.href)', $script);
        $this->assertStringContainsString('namespaceDetailIds(detail,doc)', $script);
        $this->assertStringContainsString('serializeDetailNode(detail)', $script);
        $this->assertStringNotContainsString('detail.outerHTML', $script);
        $this->assertStringContainsString('const detailClasses=new Set', $script);
        $this->assertStringContainsString('generation+=1;', $script);
        $this->assertStringContainsString('win.history.back();', $script);
        $this->assertStringContainsString('closeTimer=setTimer', $script);
        $this->assertStringNotContainsString("querySelectorAll('style')", $script);
    }

    public function test_shared_detail_dialog_supports_an_optional_dynamic_heading(): void
    {
        $dialog = file_get_contents(resource_path('views/components/erp/detail-dialog.blade.php'));

        $this->assertStringContainsString("'titleBinding' => null", $dialog);
        $this->assertStringContainsString('x-text="{{ $titleBinding }}"', $dialog);
        $this->assertStringContainsString('>{{ $title }}</h2>', $dialog);
    }
}
