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

expect()->extend('sameHtml', function (string $html) {
    expect(cleanHtml($this->value))->toBe(cleanHtml($html));
});
