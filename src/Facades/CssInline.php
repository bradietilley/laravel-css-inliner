<?php

declare(strict_types=1);

namespace LaravelCssInliner\Facades;

use Illuminate\Support\Facades\Facade;
use LaravelCssInliner\CssInliner;
use Symfony\Component\Mime\Email;

/**
 * @mixin CssInliner
 *
 * @method static void enableDebug()
 * @method static void disableDebug()
 * @method static void flushDebugLog()
 * @method static array getDebugLog()
 * @method static CssInliner debug(string $message)
 * @method static CssInliner addCss(string|SplFileInfo $css)
 * @method static CssInliner addCssPath(string|SplFileInfo $file)
 * @method static CssInliner addCssRaw(string $css)
 * @method static CssInliner clearCss()
 * @method static bool emailListenerEnabled()
 * @method static CssInliner enableEmailListener()
 * @method static CssInliner disableEmailListener()
 * @method static bool cssFromHtmlContentEnabled()
 * @method static CssInliner enableCssExtractionFromHtmlContent()
 * @method static CssInliner disableCssExtractionFromHtmlContent()
 * @method static bool cssRemovalFromHtmlContentEnabled()
 * @method static CssInliner enableCssRemovalFromHtmlContent()
 * @method static CssInliner disableCssRemovalFromHtmlContent()
 * @method static string readCssFileAsString(string $file)
 * @method static CssInliner interceptCssFile(string $file, callable $callback)
 * @method static CssInliner interceptCssFiles(callable $callback)
 * @method static CssInliner clearInterceptors()
 * @method static Email convertEmail(Email $email)
 * @method static string convert(string $html)
 * @method static void stripCssFromHtml(string &$html)
 * @method static string parseCssFromHtml(string &$html)
 * @method static CssInliner beforeConvertingEmail(callable $callback)
 * @method static CssInliner afterConvertingEmail(callable $callback)
 * @method static CssInliner beforeConvertingHtml(callable $callback)
 * @method static CssInliner afterConvertingHtml(callable $callback)
 * @method static CssInliner instance()
 * @method static array cssFiles()
 * @method static array cssRaw()
 * @method static false halt()
 */
class CssInline extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return CssInliner::class;
    }
}
