<?php

declare(strict_types=1);

use LaravelCssInliner\Tests\TestCase;

uses(TestCase::class)->in('Feature');

function cleanHtml(string $html): string
{
    return trim(
        preg_replace(
            pattern: '/.*<body>(?:<p>)?(.+?)(?:<\/p>)?<\/body>.*/s',
            replacement: '$1',
            subject: $html,
        ),
    );
}

function getTempFilePath(string $name): string
{
    if (! is_dir(__DIR__.'/.temp')) {
        mkdir(__DIR__.'/.temp');
    }

    return __DIR__.'/.temp/'.$name;
}

function writeTempFile(string $name, string $content): string
{
    file_put_contents($path = getTempFilePath($name), $content);

    return $path;
}

expect()->extend('sameHtml', function (string $html) {
    expect(cleanHtml($this->value))->toBe(cleanHtml($html));

    return $this;
});
