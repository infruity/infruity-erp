<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        // Layout memakai @vite. Test tidak butuh hasil build (`npm run build`),
        // jadi tag Vite dikosongkan agar test tetap jalan tanpa manifest.
        $this->withoutVite();
    }
}
