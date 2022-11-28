<?php

declare(strict_types=1);

namespace LaravelCssInliner\Tests;

use LaravelCssInliner\LaravelCssInlinerServiceProvider;
use Mockery;
use Mockery\MockInterface;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

class TestCase extends \Orchestra\Testbench\TestCase
{
    protected Logger|MockInterface $logger;

    protected function getPackageProviders($app): array
    {
        return [LaravelCssInlinerServiceProvider::class];
    }

    protected function getPackageAliases($app): array
    {
        return [];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->logger = Mockery::spy(Logger::class);

        $this->app->singleton(LoggerInterface::class, fn () => $this->logger);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        foreach (scandir(__DIR__.'/.temp') ?: [] as $file) {
            if ($file[0] !== '.') {
                unlink(__DIR__."/.temp/{$file}");
            }
        }
    }
}
