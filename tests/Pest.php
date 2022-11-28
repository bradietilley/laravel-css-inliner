<?php

declare(strict_types=1);

use LaravelCssInliner\Facades\CssInline;
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

expect()->extend('debugLogExists', function () {
    /** @var \Pest\Expectation $this */
    $logs = collect(CssInline::getDebugLog())
        ->map(fn (string $log) => explode(' | ', $log)[1])
        ->map(fn (string $log) => preg_split('/[:,]/', $log))
        ->toArray();

    $expect = $this->value;

    if (! is_array($expect)) {
        $expect = [$expect];
    }

    foreach ($logs as $log) {
        if ($log[0] === $expect[0]) {
            // found

            foreach ($expect as $part => $expectValue) {
                expect($log[$part])->toBe($expectValue);
            }

            return $this;
        }
    }

    // Will fail but that's fine; we just want a relevant error
    expect($logs)->toContain($expect);

    return $this;
});

expect()->extend('debugLogNotExists', function () {
    /** @var \Pest\Expectation $this */
    return $this->not()->debugLogExists();
});
