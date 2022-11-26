<?php

use LaravelCssInliner\CssInliner;
use LaravelCssInliner\Facades\CssInline;

it('can add raw css to the instance', function () {
    $styles1 = '.font-bold { font-weight: bold; }';
    $styles2 = '.italic { font-style: italic; }';

    CssInline::addCssRaw($styles1);
    CssInline::addCssRaw($styles2);

    expect(CssInline::cssRaw())->toBe([
        $styles1,
        $styles2,
    ]);
});

it('can add css files to the instance', function () {
    CssInline::addCssPath($file1 = getTempFilePath('styles1.css'));
    CssInline::addCssPath($file2 = getTempFilePath('styles2.css'));

    expect(CssInline::cssFiles())->toBe([
        $file1,
        $file2,
    ]);
});

it('can add css files and raw css to the instance', function () {
    $styles1 = '.font-bold { font-weight: bold; }';
    $styles2 = '.italic { font-style: italic; }';

    $file1 = getTempFilePath('styles1.css');
    $file2 = getTempFilePath('styles2.css');
    $file3 = 'https://example.org/main.css';
    $file4 = 'http://example.com/main.css';

    CssInline::addCss($styles1);
    CssInline::addCss($file1);
    CssInline::addCss($styles2);
    CssInline::addCss($file2);
    CssInline::addCss($file3);
    CssInline::addCss($file4);

    expect(CssInline::cssRaw())->toBe([
        $styles1,
        $styles2,
    ]);

    expect(CssInline::cssFiles())->toBe([
        $file1,
        $file2,
        $file3,
        $file4,
    ]);
});
