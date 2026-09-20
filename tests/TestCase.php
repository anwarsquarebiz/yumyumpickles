<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        /*
         | Tests assert Inertia components, not compiled assets. New pages are
         | not in public/build/manifest.json until `npm run build` runs.
         */
        $this->withoutVite();
    }
}
