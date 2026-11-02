<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Run migrations for supplemental connections used in tests (once).
     */
    protected static bool $migrationsRun = false;

    protected function setUp(): void
    {
        parent::setUp();

        if (!static::$migrationsRun) {
            $this->artisan('migrate', ['--database' => 'logging']);
            static::$migrationsRun = true;
        }
    }
}
