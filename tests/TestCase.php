<?php

declare(strict_types=1);

namespace LaravelCssInliner\Tests;

use LaravelCssInliner\CssInliner;
use LaravelCssInliner\LaravelCssInlinerServiceProvider;

class TestCase extends \Orchestra\Testbench\TestCase
{
    protected function getPackageProviders($app): array
    {
        return [LaravelCssInlinerServiceProvider::class];
    }

    protected function getPackageAliases($app): array
    {
        return [
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        CssInliner::enableDebug();
        CssInliner::flushDebugLog();

        // New instance
        app()->singleton(CssInliner::class, fn () => new CssInliner());
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        foreach (scandir(__DIR__.'/temp') as $file) {
            if ($file[0] !== '.') {
                unlink(__DIR__.'/temp/'.$file);
            }
        }
    }
}
