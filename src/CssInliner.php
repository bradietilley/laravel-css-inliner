<?php

declare(strict_types=1);

namespace LaravelCssInliner;

use Carbon\Carbon;
use Illuminate\Support\Facades\Event;
use LaravelCssInliner\Events\PostCssInlineEvent;
use LaravelCssInliner\Events\PostEmailCssInlineEvent;
use LaravelCssInliner\Events\PreCssInlineEvent;
use LaravelCssInliner\Events\PreEmailCssInlineEvent;
use SplFileInfo;
use Symfony\Component\Mime\Email;
use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;
use Illuminate\Support\Str;

class CssInliner
{
    /** Unique instance ID */
    protected string $instance;

    /** CSS files to add to HTML that is converted by CssInliner */
    protected array $cssFiles = [];

    /** Raw CSS to add to HTML that is converted by CssInliner */
    protected array $cssRaw = [];

    /** Should CssInliner read style and link elements from the given HTML? */
    protected bool $cssFromHtmlContentEnabled = false;

    /** Should CssInliner listen to email events and automatically conver them to inline CSS? */
    protected bool $emailListenerEnabled = true;

    /** Should CssInliner strip style and link elements from the given HTML after */
    protected bool $cssRemovalFromHtmlContentEnabled = false;

    /** Should CssInliner continue to convert CSS to inline styles (resets every conversion; used for events) */
    protected bool $process = true;

    /** Callback interceptors for reading CSS files */
    protected array $interceptCssFiles = [];

    /** Is debug mode enabled? */
    protected static bool $debug = false;

    /** Debug logs */
    protected static array $log = [];

    /**
     * Create a new CssInliner instance
     *
     * For Laravel: use the facade or `app(CssInliner::class)`
     */
    public static function create(): self
    {
        return new self();
    }

    public function __construct()
    {
        $this->instance = (string) Str::uuid();
    }

    /**
     * Enable debug mode
     */
    public static function enableDebug(): void
    {
        static::$debug = true;
    }

    /**
     * Disable debug mode
     */
    public static function disableDebug(): void
    {
        static::$debug = false;
    }

    /**
     * Log some debug information for when debug mode is enabled
     */
    public function debug(string $message): self
    {
        if (static::$debug) {
            static::$log[] = sprintf(
                '[%s]: %s | %s',
                Carbon::now()->toDateTimeString(),
                $this->instance,
                $message,
            );
        }

        return $this;
    }

    /**
     * Add a CSS file to every email/HTML that gets converted
     * by CSS Inliner
     */
    public function addCssPath(string|SplFileInfo $file): self
    {
        $file = ($file instanceof SplFileInfo) ? $file->getRealPath() : $file;
        $this->debug('Registered new CSS file: ' . $file);
        $this->cssFiles[] = $file;

        return $this;
    }

    /**
     * Add some raw CSS to every email/HTML that gets converted
     * by CSS Inliner
     */
    public function addCssRaw(string $css): self
    {
        $this->debug(sprintf('Registered new raw CSS (total %s characters)', strlen($css)));
        $this->cssRaw[] = $css;

        return $this;
    }

    /**
     * Reset any CSS added to this CSS Inliner singleton
     */
    public function clearCss(): self
    {
        $this->debug('Registered CSS cleared');
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
        $this->debug('Enabled email listener');
        $this->emailListenerEnabled = true;

        return $this;
    }

    /**
     * Disable automatic CSS inlining for Laravel-dispatched Emails
     */
    public function disableEmailListener(): self
    {
        $this->debug('Disabled email listener');
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
        $this->debug('Enabled CSS extraction from HTML content');
        $this->cssFromHtmlContentEnabled = true;

        return $this;
    }

    /**
     * Do not read inline <style> and <link> elements from within the given HTML
     */
    public function disableCssExtractionFromHtmlContent(): self
    {
        $this->debug('Disabled CSS extraction from HTML content');
        $this->cssFromHtmlContentEnabled = true;

        return $this;
    }

    /**
     * Should CssInliner strip style and link elements from the given HTML after
     */
    public function cssRemovalFromHtmlContentEnabled(): bool
    {
        return $this->cssRemovalFromHtmlContentEnabled;
    }

    /**
     * Remove <style> and <link> elements from within the given HTML after conversion
     */
    public function enableCssRemovalFromHtmlContent(): self
    {
        $this->debug('Enabled CSS Removal from HTML content');
        $this->cssRemovalFromHtmlContentEnabled = true;

        return $this;
    }

    /**
     * Don't remove <style> and <link> elements from within the given HTML after conversion
     */
    public function disableCssRemovalFromHtmlContent(): self
    {
        $this->debug('Disabled CSS Removal from HTML content');
        $this->cssRemovalFromHtmlContentEnabled = true;

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
        $this->debug('Reading CSS file as string: ' . $file);

        if (isset($this->interceptCssFiles[$file])) {
            $this->debug('Interceptor for file exists; running interceptor');
            $callback = $this->interceptCssFiles[$file];

            return $callback($file, $this);
        }

        if (isset($this->interceptCssFiles['*'])) {
            $this->debug('Global file interceptor exists; running interceptor');
            $callback = $this->interceptCssFiles['*'];

            return $callback($file, $this);
        }

        $this->debug('Reading CSS file via file_get_contents');

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
        $this->debug(sprintf('File-specific interceptor registered (%s)', $file));
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
        $this->debug('Global file interceptor registered');
        $this->interceptCssFiles['*'] = $callback;

        return $this;
    }

    /**
     * Clear any file interceptors added via CssInliner::interceptCssFiles()
     */
    public function clearInterceptors(): self
    {
        $this->debug('Interceptors cleared');
        $this->interceptCssFiles = [];

        return $this;
    }

    /**
     * Convert the given Email HTML content to use inline styles
     */
    public function convertEmail(Email $email): Email
    {
        $this->debug('Email conversion started.');

        // Don't change anything if the email listener is disabled
        if ($this->emailListenerEnabled() === false) {
            $this->debug('Email listener is disabled; skipping conversion.');

            return $email;
        }

        Event::dispatch(new PreEmailCssInlineEvent($email, $this));

        if ($this->process === false) {
            $this->debug('Processing has been disabled; skipping conversion.');

            return $email;
        }

        $body = $email->getHtmlBody();

        if (is_resource($body)) {
            $this->debug('Email body is resource; skipping conversion.');

            return $email;
        }

        $email->html($this->convert($body));

        Event::dispatch(new PostEmailCssInlineEvent($email, $this));

        $this->debug('Email conversion finished.');

        return $email;
    }

    /**
     * Convert the given HTML content to use inline styles
     */
    public function convert(string $html): string
    {
        $this->debug('HTML conversion started.');

        Event::dispatch(new PreCssInlineEvent($html, $this));

        if ($this->process === false) {
            $this->debug('Processing has been disabled; skipping conversion.');

            return $html;
        }

        if (empty($html)) {
            $this->debug('HTML empty; skipping conversion');

            return $html;
        }

        $files = collect($this->cssFiles)->map(fn (string $file) => $this->readCssFileAsString($file));
        $this->debug(sprintf('CSS Files read (total %d)', $files->count()));

        $raw = collect($this->cssRaw);
        $this->debug(sprintf('Raw CSS entries read (total %d)', $files->count()));

        $htmlCss = ($this->cssFromHtmlContentEnabled)
            ? $this->parseCssFromHtml($html)
            : null;

        $this->debug(
            ($htmlCss === null)
                ? 'CSS within HTML content ignored'
                : sprintf('CSS within HTML content parsed (total %s characters)', strlen($html)),
        );

        $css = collect([
            $files->implode("\n\n"),
            $raw->implode("\n\n"),
            $htmlCss ?? '',
        ])->filter()->implode("\n\n");

        $this->debug(sprintf('All CSS (total %s characters)', strlen($css)));

        $lengthWas = strlen($html);

        $inliner = new CssToInlineStyles();
        $html = $inliner->convert($html, $css);

        $lengthNow = strlen($html);

        Event::dispatch(new PostCssInlineEvent($html, $this));

        $this->debug('HTML conversion finished.');
        $this->debug('HTML size was %s, now %s.', $lengthWas, $lengthNow);

        return $html;
    }

    /**
     * Extract and return CSS from the given HTML
     */
    public function parseCssFromHtml(string &$html): string
    {
        $this->debug('Parsing CSS from HTML started');

        $raw = [];
        $lengthWas = strlen($html);

        $html = preg_replace_callback(
            pattern: '/<style[^>]*>(.+?)<\/style>/s',
            callback: function (array $matches) use (&$raw) {
                $raw[] = $css = $matches[1];

                $this->debug(sprintf('Style element extracted (total %s characters)', strlen($css)));

                if ($this->cssRemovalFromHtmlContentEnabled()) {
                    $this->debug('Style element removed from HTML');

                    return '';
                }

                $this->debug('Style element retained in HTML');

                return $matches[0];
            },
            subject: $html,
        );

        $html = preg_replace_callback(
            pattern: [
                '/<link[^>]+href="([^"]+)"[^>]+rel="stylesheet"[^>]*>/',
                '/<link[^>]+rel="stylesheet"[^>]+href="([^"]+)"[^>]*>/',
            ],
            callback: function (array $matches) use (&$raw) {
                $file = $matches[1];
                $this->debug(sprintf('Link stylesheet element extracted (path: %s)', $file));

                $raw[] = $css = $this->readCssFileAsString($file);
                $this->debug(sprintf('Link stylesheet resolved (total %s characters)', strlen($css)));

                if ($this->cssRemovalFromHtmlContentEnabled()) {
                    $this->debug('Link stylesheet element removed from HTML');

                    return '';
                }

                $this->debug('Link stylesheet element retained in HTML');

                return $matches[0];
            },
            subject: $html,
        );

        $this->debug('Parsed CSS (total %s elements)', count($raw));

        $css = implode("\n\n", $raw);
        $lengthNow = strlen($html);

        $this->debug('Parsing CSS from HTML finished');
        $this->debug('HTML size was %s, now %s.', $lengthWas, $lengthNow);

        return $css;
    }

    public function beforeConvertingEmail(callable $callback): self
    {
        $this->debug('Registered callback: beforeConvertingEmail');
        Event::listen(PreEmailCssInlineEvent::class, fn (PreEmailCssInlineEvent $event) => $callback($event));

        return $this;
    }

    public function afterConvertingEmail(callable $callback): self
    {
        $this->debug('Registered callback: afterConvertingEmail');
        Event::listen(PostEmailCssInlineEvent::class, fn (PostEmailCssInlineEvent $event) => $callback($event));

        return $this;
    }

    public function beforeConvertingHtml(callable $callback): self
    {
        $this->debug('Registered callback: beforeConvertingHtml');
        Event::listen(PreCssInlineEvent::class, fn (PreCssInlineEvent $event) => $callback($event));

        return $this;
    }

    public function afterConvertingHtml(callable $callback): self
    {
        $this->debug('Registered callback: afterConvertingHtml');
        Event::listen(PostCssInlineEvent::class, fn (PostCssInlineEvent $event) => $callback($event));

        return $this;
    }

    public function instance(): self
    {
        return $this;
    }

    public function cssFiles(): array
    {
        return $this->cssFiles;
    }

    public function cssRaw(): array
    {
        return $this->cssRaw;
    }

    /**
     * Halt the conversion of CSS to inline styles
     */
    public function halt(): void
    {
        $this->process = false;
    }
}
