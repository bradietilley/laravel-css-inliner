<?php

declare(strict_types=1);

namespace LaravelCssInliner;

use Illuminate\Support\Facades\Event;
use LaravelCssInliner\Events\PostCssInlineEvent;
use LaravelCssInliner\Events\PostEmailCssInlineEvent;
use LaravelCssInliner\Events\PreCssInlineEvent;
use LaravelCssInliner\Events\PreEmailCssInlineEvent;
use SplFileInfo;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Message;
use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;

class CssInliner
{
    /** CSS files to add to HTML that is converted by CssInliner */
    protected array $cssFiles = [];

    /** Raw CSS to add to HTML that is converted by CssInliner */
    protected array $cssRaw = [];

    /** Should CssInliner read style and link elements from the given HTML? */
    protected bool $cssFromHtmlContentEnabled = false;

    /** Should CssInliner listen to email events and automatically conver them to inline CSS? */
    protected bool $emailListenerEnabled = true;

    /** Callback interceptors for reading CSS files */
    protected array $interceptCssFiles = [];

    /**
     * Add a CSS file to every email/HTML that gets converted
     * by CSS Inliner
     */
    public function addCss(string|SplFileInfo $file): self
    {
        $this->cssFiles[] = ($file instanceof SplFileInfo) ? $file->getRealPath() : $file;

        return $this;
    }

    /**
     * Add some raw CSS to every email/HTML that gets converted
     * by CSS Inliner
     */
    public function addCssRaw(string $css): self
    {
        $this->cssRaw[] = $css;

        return $this;
    }

    /**
     * Reset any CSS added to this CSS Inliner singleton
     */
    public function clearCss(): self
    {
        $this->cssFiles = [];
        $this->cssRaw = [];

        return $this;
    }

    /**
     * Should CssInliner listen to email events and automatically conver them to inline CSS?
     */
    public function emailListenerEnabled(): bool
    {
        return $this->emailListenerEnabled;
    }

    /**
     * Enable automatic CSS inlining for Laravel-dispatched Emails
     */
    public function enableEmailListener(): self
    {
        $this->emailListenerEnabled = true;

        return $this;
    }

    /**
     * Disable automatic CSS inlining for Laravel-dispatched Emails
     */
    public function disableEmailListener(): self
    {
        $this->emailListenerEnabled = true;

        return $this;
    }

    /**
     * Should CssInliner read style and link elements from the given HTML?
     */
    public function cssFromHtmlContentEnabled(): bool
    {
        return $this->cssFromHtmlContentEnabled;
    }

    /**
     * Read inline <style> and <link> elements from within the given HTML
     */
    public function enableCssExtractionFromHtmlContent(): self
    {
        $this->cssFromHtmlContentEnabled = true;

        return $this;
    }

    /**
     * Do not read inline <style> and <link> elements from within the given HTML
     */
    public function disableCssExtractionFromHtmlContent(): self
    {
        $this->cssFromHtmlContentEnabled = true;

        return $this;
    }

    /**
     * Read the given CSS file as a string.
     *
     * Will proxy to any interceptor callbacks called via:
     *     CssInliner::interceptCssFile(); // or:
     *     CssInliner::interceptCssFiles();
     *
     * before proceeding to read the contents of the file via
     * a standard file_get_contents()
     */
    public function readCssFileAsString(string $file): string
    {
        if (isset($this->interceptCssFiles[$file])) {
            $callback = $this->interceptCssFiles[$file];

            return $callback($file, $this);
        }

        if (isset($this->interceptCssFiles['*'])) {
            $callback = $this->interceptCssFiles['*'];

            return $callback($file, $this);
        }

        return file_get_contents($file);
    }

    /**
     * Intercept the reading of any CSS file and use a custom callback to handle
     * the reading of the file.
     *
     * Usage:
     *
     *      $css = '/path/to/some_vendor_css.css';
     *
     *      CssInliner::interceptCssFile($css, function (string $file, CssInliner $inliner) {
     *          return '.bold { font-weight: bold; }';
     *      });
     */
    public function interceptCssFile(string $file, callable $callback): self
    {
        $this->interceptCssFiles[$file] = $callback;

        return $this;
    }

    /**
     * Intercept the reading of any CSS file and use a custom callback to handle
     * the reading of the file.
     *
     * Usage:
     *
     *      CssInliner::interceptCssFiles(function (string $file, CssInliner $inliner) {
     *          if (str_starts_with($file, 'https://')) {
     *              return MyCustomClass::cacheRemoteFile($file);
     *          }
     *
     *          return file_get_contents($file);
     *      });
     */
    public function interceptCssFiles(callable $callback): self
    {
        $this->interceptCssFiles['*'] = $callback;

        return $this;
    }

    /**
     * Clear any file interceptors added via CssInliner::interceptCssFiles()
     */
    public function clearInterceptors(): self
    {
        $this->interceptCssFiles = [];

        return $this;
    }

    /**
     * Convert the given Email HTML content to use inline styles
     */
    public function convertEmail(Email $email): Message
    {
        // Don't change anything if the email listener is disabled
        if ($this->emailListenerEnabled() === false) {
            return $email;
        }

        $response = Event::dispatch(new PreEmailCssInlineEvent($email, $this));

        if ($response === false) {
            return $email;
        }

        $email->html(
            self::convert($email->getHtmlBody()),
        );

        Event::dispatch(new PostEmailCssInlineEvent($email, $this));

        return $email;
    }

    /**
     * Convert the given HTML content to use inline styles
     */
    public function convert(string $html): string
    {
        $response = Event::dispatch(new PreCssInlineEvent($html, $this));

        if ($response === false) {
            return $html;
        }

        $css = [
            'files' => collect($this->cssFiles)
                ->map(fn (string $file) => $this->readCssFileAsString($file))
                ->implode("\n\n"),
            'raw' => implode("\n\n", $this->cssRaw),
            'html' => ($this->cssFromHtmlContentEnabled)
                ? $this->parseCssFromHtml($html)
                : '',
        ];

        $inliner = new CssToInlineStyles();
        $css = collect($css)->values()->implode("\n\n");
        $html = $inliner->convert($html, $css);

        Event::dispatch(new PostCssInlineEvent($html, $this));

        return $html;
    }

    /**
     * Extract CSS from the given HTML
     */
    public function parseCssFromHtml(string $html): string
    {
        // TODO
        return '';
    }

    public function beforeConvertingEmail(callable $callback): self
    {
        Event::listen(PreEmailCssInlineEvent::class, $callback);

        return $this;
    }

    public function afterConvertingEmail(callable $callback): self
    {
        Event::listen(PostEmailCssInlineEvent::class, $callback);

        return $this;
    }

    public function beforeConvertingHtml(callable $callback): self
    {
        Event::listen(PreCssInlineEvent::class, $callback);

        return $this;
    }

    public function afterConvertingHtml(callable $callback): self
    {
        Event::listen(PostCssInlineEvent::class, $callback);

        return $this;
    }
}
