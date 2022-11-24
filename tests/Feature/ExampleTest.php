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
    $file = __DIR__ . '/temp.css';

    if (file_exists($file)) {
        unlink($file);
    }

    $css = '.font-bold { font-weight: bold; }';
    file_put_contents($file, $css);

    $html = 'This is a <span class="font-bold">test</span>';
    $expect = 'This is a <span class="font-bold" style="font-weight: bold;">test</span>';

    $actual = CssInliner::create()
        ->addCss($file)
        ->convert($html);

    expect($actual)->sameHtml($expect);
});
