<?php

declare(strict_types=1);

namespace LaravelCssInliner\Tests;

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
}
