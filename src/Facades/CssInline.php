<?php

declare(strict_types=1);

namespace LaravelCssInliner\Facades;

use Illuminate\Support\Facades\Facade;
use LaravelCssInliner\CssInliner;
use Symfony\Component\Mime\Email;

/**
 * @mixin CssInliner
 *
 * @method static CssInliner addCss(string|SplFileInfo $file)
 * @method static CssInliner addCssRaw(string $css)
 * @method static CssInliner clearCss()
 * @method static CssInliner extractCssFromHtmlContent()
 * @method static CssInliner dontExtractCssFromHtmlContent()
 * @method static string readCssFileAsString(string $file)
 * @method static Email convertEmail(string Email)
 * @method static string convert(string $html)
 * @method static string parseCssFromHtml(string $html)
 */
class CssInline extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return CssInliner::class;
    }
}
