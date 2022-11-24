<?php

declare(strict_types=1);

use LaravelCssInliner\Tests\TestCase;

uses(TestCase::class)->in('Feature');

function cleanHtml(string $html): string
{
    return trim(
        preg_replace(
            pattern: '/.*<body><p>(.+)<\/p><\/body>.*/s',
            replacement: '$1',
            subject: $html,
        ),
    );
}

function getTempFilePath(string $name): string
{
    return __DIR__ . '/temp/' . $name;
}

function writeTempFile(string $name, string $content): string
{
    file_put_contents($path = getTempFilePath($name), $content);

    return $path;
}

afterAll(function () {
    foreach (scandir(__DIR__ . '/temp') as $file) {
        if ($file[0] !== '.') {
            unlink(__DIR__ . '/temp/' . $file);
        }
    }
});

expect()->extend('sameHtml', function (string $html) {
    expect(cleanHtml($this->value))->toBe(cleanHtml($html));
});
