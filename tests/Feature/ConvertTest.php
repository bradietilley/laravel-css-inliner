<?php

declare(strict_types=1);

use LaravelCssInliner\CssInliner;

it('can convert css classes to inline styles using raw CSS', function () {
    $html = 'This is a <span class="font-bold">test</span>';
    $css = '.font-bold { font-weight: bold; }';

    $expect = 'This is a <span class="font-bold" style="font-weight: bold;">test</span>';

    $actual = $this->app->make(CssInliner::class)
        ->addCssRaw($css)
        ->convert($html);

    expect($actual)->sameHtml($expect);
});

it('can convert css classes to inline styles using CSS file', function () {
    $css = '.font-bold { font-weight: bold; }';
    $file = writeTempFile('example.css', $css);

    $html = 'This is a <span class="font-bold">test</span>';
    $expect = 'This is a <span class="font-bold" style="font-weight: bold;">test</span>';

    $actual = $this->app->make(CssInliner::class)
        ->addCssPath($file)
        ->convert($html);

    expect($actual)->sameHtml($expect);
});

it('can convert css classes to inline styles using embedded style element', function () {
    $css = '.example { text-decoration: underline; }';
    $html = '<style>'.$css.'</style> This is a <span class="example">test</span>';

    $expect = 'This is a <span class="example" style="text-decoration: underline;">test</span>';

    $actual = $this->app->make(CssInliner::class)
        ->enableCssExtractionFromHtmlContent()
        ->disableCssRemovalFromHtmlContent()
        ->convert($html);

    expect($actual)->sameHtml($expect);
});

it('can convert css classes to inline styles using embedded link element as local file', function () {
    $css = '.example { font-style: italic; }';
    $path = writeTempFile('mail.css', $css);
    $html = '<link rel="stylesheet" href="'.$path.'"> This is a <span class="example">test</span>';

    $expect = 'This is a <span class="example" style="font-style: italic;">test</span>';

    $actual = $this->app->make(CssInliner::class)
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

    $actual = $this->app->make(CssInliner::class)
        ->addCssPath($file1)
        ->addCssPath($file2)
        ->addCssRaw($css1)
        ->addCssRaw($css2)
        ->convert($html);

    expect($actual)->sameHtml($expect);
});

it('can convert css classes to inline styles and remove style and link elements from html', function () {
    $path1 = getTempFilePath('main.css');
    $path2 = getTempFilePath('other.css');

    file_put_contents($path1, '.underline { text-decoration: underline; }');
    file_put_contents($path2, '.font-lg { font-size: 4em; }');

    $html = <<<HTML
    <html>
        <head>
            <style>
                .font-bold {
                    font-weight: bold;
                }
            </style>
            <style>
                .italic {
                    font-style: italic;
                }
            </style>

            <link rel="stylesheet" href="{$path1}">
            <link href="{$path2}" rel="stylesheet">
        </head>
        <body>
            <span class="font-bold italic underline font-lg">Nice</span>
        </body>
    </html>
    HTML;

    /**
     * Enabled
     */
    $actual = $this->app->make(CssInliner::class)
        ->enableCssExtractionFromHtmlContent()
        ->enableCssRemovalFromHtmlContent()
        ->convert($html);

    $expect = '<span class="font-bold italic underline font-lg" style="font-weight: bold; font-style: italic; font-size: 4em; text-decoration: underline;">Nice</span>';
    expect($actual)->sameHtml($expect);

    preg_match('/<head>(.+)<\/head>/is', $actual, $matches);

    $head = trim($matches[1]);
    expect($head)->toBe('');

    /**
     * Disabled
     */
    $actual = $this->app->make(CssInliner::class)
        ->enableCssExtractionFromHtmlContent()
        ->disableCssRemovalFromHtmlContent()
        ->convert($html);

    $expect = '<span class="font-bold italic underline font-lg" style="font-weight: bold; font-style: italic; font-size: 4em; text-decoration: underline;">Nice</span>';
    expect($actual)->sameHtml($expect);

    preg_match('/<head>(.+)<\/head>/is', $actual, $matches);

    $head = trim($matches[1]);
    expect($head)->not()->toBe('');
});

it('will not parse css style and link elements from html if disabled', function () {
    $path1 = getTempFilePath('main.css');
    $path2 = getTempFilePath('other.css');

    file_put_contents($path1, '.underline { text-decoration: underline; }');
    file_put_contents($path2, '.font-lg { font-size: 4em; }');

    $html = <<<HTML
    <html>
        <head>
            <style>
                .font-bold {
                    font-weight: bold;
                }
            </style>
            <style>
                .italic {
                    font-style: italic;
                }
            </style>

            <link rel="stylesheet" href="{$path1}">
            <link href="{$path2}" rel="stylesheet">
        </head>
        <body>
            <span class="font-bold italic underline font-lg">Nice</span>
        </body>
    </html>
    HTML;

    /**
     * Enabled
     */
    $actual = $this->app->make(CssInliner::class)
        ->enableCssExtractionFromHtmlContent()
        ->disableCssRemovalFromHtmlContent()
        ->convert($html);

    $expect = '<span class="font-bold italic underline font-lg" style="font-weight: bold; font-style: italic; font-size: 4em; text-decoration: underline;">Nice</span>';
    expect($actual)->sameHtml($expect);

    /**
     * Disabled
     */
    $actual = $this->app->make(CssInliner::class)
        ->disableCssExtractionFromHtmlContent()
        ->disableCssRemovalFromHtmlContent()
        ->convert($html);

    $expect = '<span class="font-bold italic underline font-lg">Nice</span>';
    expect($actual)->sameHtml($expect);
});
