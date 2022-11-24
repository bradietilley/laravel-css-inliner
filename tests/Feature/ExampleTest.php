<?php

declare(strict_types=1);

use LaravelCssInliner\CssInliner;

it('can convert css classes to inline styles using raw CSS', function () {
    $html = 'This is a <span class="font-bold">test</span>';
    $css = '.font-bold { font-weight: bold; }';

    $expect = 'This is a <span class="font-bold" style="font-weight: bold;">test</span>';

    $actual = CssInliner::create()
        ->addCssRaw($css)
        ->convert($html);

    expect($actual)->sameHtml($expect);
});

it('can convert css classes to inline styles using CSS file', function () {
    $css = '.font-bold { font-weight: bold; }';
    $file = writeTempFile('example.css', $css);

    $html = 'This is a <span class="font-bold">test</span>';
    $expect = 'This is a <span class="font-bold" style="font-weight: bold;">test</span>';

    $actual = CssInliner::create()
        ->addCss($file)
        ->convert($html);

    expect($actual)->sameHtml($expect);
});

it('can convert css classes to inline styles using multiple sources', function () {
    $file1 = writeTempFile('example1.css', '.example { font-weight: bold; }');
    $file2 = writeTempFile('example2.css', '.example { font-size: 12px; }');

    $css1 = '.example { color: red; }';
    $css2 = '.example { text-decoration: underline; }';

    $html = 'This is a <span class="example">test</span>';

    $expect = 'This is a <span class="example" style="font-weight: bold; font-size: 12px; color: red; text-decoration: underline;">test</span>';

    $actual = CssInliner::create()
        ->addCss($file1)
        ->addCss($file2)
        ->addCssRaw($css1)
        ->addCssRaw($css2)
        ->convert($html);

    expect($actual)->sameHtml($expect);
});
