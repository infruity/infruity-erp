<?php

namespace Tests\Feature;

use Tests\TestCase;

final class SearchWildcardEscapeTest extends TestCase
{
    /** Controllers whose list or picker searches were checked and now go through App\Support\Like. */
    private const CONTROLLERS = [
        'Modules/Master/Http/Controllers/CustomerController.php',
        'Modules/Master/Http/Controllers/ProductController.php',
        'Modules/Transaction/Http/Controllers/ProductionController.php',
        'Modules/Transaction/Http/Controllers/ProductReceiptController.php',
        'Modules/Transaction/Http/Controllers/SortirController.php',
        'Modules/Transaction/Http/Controllers/StockOpnameController.php',
        'Modules/Transaction/Http/Controllers/TransferController.php',
        'Modules/Transaction/Http/Controllers/WholesaleController.php',
    ];

    public function test_no_user_keyword_is_put_into_a_like_pattern_unescaped(): void
    {
        foreach (self::CONTROLLERS as $path) {
            $source = file_get_contents(base_path($path));

            $this->assertDoesNotMatchRegularExpression(
                '/[\'"](?:like|LIKE)[\'"]\s*,\s*(?:\'%\'\s*\.|"%\{\$)/',
                $source,
                "$path builds a LIKE pattern from a raw keyword; use App\\Support\\Like::contains()."
            );
            $this->assertStringContainsString('use App\\Support\\Like;', $source, $path);
        }
    }

    public function test_like_helpers_accept_a_missing_keyword_like_the_old_interpolation_did(): void
    {
        // An empty query-string value (?term=) arrives as null after ConvertEmptyStringsToNull.
        $this->assertSame('%%', \App\Support\Like::contains(null));
        $this->assertSame('', \App\Support\Like::escape(null));
        $this->assertSame('%100\\%%', \App\Support\Like::contains('100%'));
    }
}
