<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Test merender root view tanpa bergantung pada hasil `npm run build`.
        $this->withoutVite();
    }
}
