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
        ->addCssPath($file)
        ->convert($html);

    expect($actual)->sameHtml($expect);
});

it('can convert css classes to inline styles using embedded style element', function () {
    $css = '.example { text-decoration: underline; }';
    $html = '<style>' . $css . '</style> This is a <span class="example">test</span>';

    $expect = 'This is a <span class="example" style="text-decoration: underline;">test</span>';

    $actual = CssInliner::create()
        ->enableCssExtractionFromHtmlContent()
        ->disableCssRemovalFromHtmlContent()
        ->convert($html);

    expect($actual)->sameHtml($expect);
});

it('can convert css classes to inline styles using embedded link element as local file', function () {
    $css = '.example { font-style: italic; }';
    $path = writeTempFile('mail.css', $css);
    $html = '<link rel="stylesheet" href="' . $path . '"> This is a <span class="example">test</span>';

    $expect = 'This is a <span class="example" style="font-style: italic;">test</span>';

    $actual = CssInliner::create()
        ->enableCssExtractionFromHtmlContent()
        ->disableCssRemovalFromHtmlContent()
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
        ->addCssPath($file1)
        ->addCssPath($file2)
        ->addCssRaw($css1)
        ->addCssRaw($css2)
        ->convert($html);

    expect($actual)->sameHtml($expect);
});