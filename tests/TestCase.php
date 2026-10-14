<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Tes tidak boleh bergantung pada hasil build front-end (public/build)
        // atau Vite dev server (public/hot).
        $this->withoutVite();
    }
}
