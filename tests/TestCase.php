<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Pages render without a front-end build, so the suite runs right after `composer install`.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }
}
