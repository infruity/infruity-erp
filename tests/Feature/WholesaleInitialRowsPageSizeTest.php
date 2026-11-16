<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * The rows embedded in the page for the first paint must be one page (20), not the whole table: DataTables reads
 * start/length from the request of the running page, so initialRows() has to merge them into that request.
 */
final class WholesaleInitialRowsPageSizeTest extends TestCase
{
    public function test_initial_rows_merge_the_page_size_into_the_page_request_and_restore_it(): void
    {
        $source = file_get_contents(base_path('Modules/Transaction/Http/Controllers/WholesaleController.php'));
        preg_match('/private function initialRows\(.*?\n    }\n/s', $source, $method);

        $this->assertNotEmpty($method);
        $body = $method[0];
        $this->assertStringContainsString("'start' => 0, 'length' => 20", $body);
        $this->assertStringContainsString('$request->merge($params);', $body);
        $this->assertStringContainsString('$this->get_data($request)', $body);
        $this->assertStringContainsString('$request->replace($original);', $body);
        $this->assertStringNotContainsString('Request::create(', $body, 'a separate Request is ignored by DataTables and returned every row');
    }
}
