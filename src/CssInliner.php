<?php

declare(strict_types=1);

namespace LaravelCssInliner;

use Carbon\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use LaravelCssInliner\Events\PostCssInlineEvent;
use LaravelCssInliner\Events\PostEmailCssInlineEvent;
use LaravelCssInliner\Events\PreCssInlineEvent;
use LaravelCssInliner\Events\PreEmailCssInlineEvent;
use SplFileInfo;
use Symfony\Component\Mime\Email;
use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;

class CssInliner
{
    /** Unique instance ID */
    protected string $instance;

    /** @var array<string,string> CSS files to add to HTML that is converted by CssInliner */
    protected array $cssFiles = [];

    /** @var array<int,string> Raw CSS to add to HTML that is converted by CssInliner */
    protected array $cssRaw = [];

    /** Should CssInliner read style and link elements from the given HTML? */
    protected bool $cssFromHtmlContentEnabled = false;

    /** Should CssInliner listen to email events and automatically conver them to inline CSS? */
    protected bool $emailListenerEnabled = true;

    /** Should CssInliner strip style and link elements from the given HTML after */
    protected bool $cssRemovalFromHtmlContentEnabled = false;

    /** Should CssInliner continue to convert CSS to inline styles (resets every conversion; used for events) */
    protected bool $process = true;

    /** @var array<string,callable> Callback interceptors for reading CSS files */
    protected array $interceptCssFiles = [];

    /** Is debug mode enabled? */
    protected static bool $debug = false;

    /** @var array<string> Debug logs */
    protected static array $log = [];

    /**
     * Create a new CssInliner instance
     *
     * For Laravel: it's recommended you use the facade
     * or at least `app(CssInliner::class)` to ensure that
     * you're also referencing a singleton not a stray
     * instance
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
     * Reset the log
     */
    public static function flushDebugLog(): void
    {
        static::$log = [];
    }

    /**
     * Get the debug log
     *
     * @return array<string>
     */
    public static function getDebugLog(): array
    {
        return static::$log;
    }

    /**
     * Log some debug information for when debug mode is enabled
     */
    public function debug(string $message): self
    {
        if (static::$debug) {
            $log = sprintf(
                '[%s]: %s | %s',
                Carbon::now()->toDateTimeString(),
                $this->instance,
                $message,
            );

            static::$log[] = $log;
        }

        return $this;
    }

    /**
     * Add raw CSS or a CSS file.
     */
    public function addCss(string|SplFileInfo $css): self
    {
        if ($css instanceof SplFileInfo) {
            return $this->addCssPath($css);
        }

        /** If it's multiline, it's safe to assume it's raw CSS */
        if (Str::contains($css, ["\n", "\r"])) {
            return $this->addCssRaw($css);
        }

        /** If it starts with a slash or http protocol, it's safe to assume it's a CSS file */
        if (Str::startsWith($css, ['/', 'https://', 'http://'])) {
            return $this->addCssPath($css);
        }

        /** Only thing left is a relative file, or a single line CSS */
        if (Str::endsWith($css, '.css')) {
            return $this->addCssPath($css);
        }

        /** Assume then that it's a single line CSS file :shrug: */
        return $this->addCssRaw($css);
    }

    /**
     * Add a CSS file to every email/HTML that gets converted
     * by CSS Inliner
     */
    public function addCssPath(string|SplFileInfo $file): self
    {
        $file = ($file instanceof SplFileInfo) ? $file->getRealPath() : $file;
        $this->debug('registered_new_css_file:'.$file);
        $this->cssFiles[$file] = $file;

        return $this;
    }

    /**
     * Add some raw CSS to every email/HTML that gets converted
     * by CSS Inliner
     */
    public function addCssRaw(string $css): self
    {
        $this->debug('registered_new_raw_css_total_characters:'.strlen($css));
        $this->cssRaw[] = $css;

        return $this;
    }

    /**
     * Reset any CSS added to this CSS Inliner singleton
     */
    public function clearCss(): self
    {
        $this->debug('registered_css_cleared');
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
        $this->debug('enabled_email_listener');
        $this->emailListenerEnabled = true;

        return $this;
    }

    /**
     * Disable automatic CSS inlining for Laravel-dispatched Emails
     */
    public function disableEmailListener(): self
    {
        $this->debug('disabled_email_listener');
        $this->emailListenerEnabled = false;

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
        $this->debug('enabled_css_extraction_from_html_content');
        $this->cssFromHtmlContentEnabled = true;

        return $this;
    }

    /**
     * Do not read inline <style> and <link> elements from within the given HTML
     */
    public function disableCssExtractionFromHtmlContent(): self
    {
        $this->debug('disabled_css_extraction_from_html_content');
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
        $this->debug('enabled_css_removal_from_html_content');
        $this->cssRemovalFromHtmlContentEnabled = true;

        return $this;
    }

    /**
     * Don't remove <style> and <link> elements from within the given HTML after conversion
     */
    public function disableCssRemovalFromHtmlContent(): self
    {
        $this->debug('disabled_css_removal_from_html_content');
        $this->cssRemovalFromHtmlContentEnabled = false;

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
        $this->debug('reading_css_file_as_string:'.$file);

        if (isset($this->interceptCssFiles[$file])) {
            $this->debug('interceptor_for_file_exists_running_interceptor');
            $callback = $this->interceptCssFiles[$file];

            return $callback($file, $this);
        }

        if (isset($this->interceptCssFiles['*'])) {
            $this->debug('global_file_interceptor_exists_running_interceptor');
            $callback = $this->interceptCssFiles['*'];

            return $callback($file, $this);
        }

        $this->debug('reading_css_file_via_file_get_contents');
        $contents = file_get_contents($file);

        if ($contents === false) {
            $this->debug('read_css_file_via_file_get_contents_failed');

            $contents = '';
        }

        return $contents;
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
        $this->debug('file_specific_interceptor_registered::'.$file);
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
        $this->debug('global_file_interceptor_registered');
        $this->interceptCssFiles['*'] = $callback;

        return $this;
    }

    /**
     * Clear any file interceptors added via CssInliner::interceptCssFiles()
     */
    public function clearInterceptors(): self
    {
        $this->debug('interceptors_cleared');
        $this->interceptCssFiles = [];

        return $this;
    }

    /**
     * Convert the given Email HTML content to use inline styles
     */
    public function convertEmail(Email $email): Email
    {
        $this->debug('email_conversion_started');

        // Don't change anything if the email listener is disabled
        if ($this->emailListenerEnabled() === false) {
            $this->debug('email_listener_is_disabled_skipping_conversion');

            return $email;
        }

        Event::dispatch(new PreEmailCssInlineEvent($email, $this));

        if ($this->process === false) {
            $this->debug('email_processing_has_been_halted_skipping_conversion');

            return $email;
        }

        $body = $email->getHtmlBody();

        if (is_resource($body)) {
            $this->debug('email_body_is_resource_skipping_conversion');

            return $email;
        }

        if ($body === null) {
            $this->debug('email_body_is_null_skipping_conversions');

            return $email;
        }

        $email->html($this->convert($body));

        Event::dispatch(new PostEmailCssInlineEvent($email, $this));

        $this->debug('email_conversion_finished');

        return $email;
    }

    /**
     * Convert the given HTML content to use inline styles
     */
    public function convert(string $html): string
    {
        $this->debug('html_conversion_started');

        Event::dispatch(new PreCssInlineEvent($html, $this));

        if ($this->process === false) {
            $this->debug('html_processing_has_been_halted_skipping_conversion');

            return $html;
        }

        if (strlen(trim($html)) === 0) {
            $this->debug('html_empty_skipping_conversion');

            return $html;
        }

        $files = collect($this->cssFiles)->map(fn (string $file) => $this->readCssFileAsString($file));
        $this->debug('css_files_read_total:'.$files->count());

        $raw = collect($this->cssRaw);
        $this->debug('raw_css_entries_read_totad:'.$files->count());

        $htmlCss = null;

        if ($this->cssFromHtmlContentEnabled) {
            $htmlCss = $this->parseCssFromHtml($html);

            $this->debug('css_within_html_content_parsed_total_s_characters:'.strlen($htmlCss));
        } else {
            $this->debug('css_within_html_content_ignored');
        }

        $css = collect([
            $files->implode("\n\n"),
            $raw->implode("\n\n"),
            $htmlCss ?? '',
        ])->filter()->implode("\n\n");

        $this->debug('all_css_total_characters:'.strlen($css));

        $lengthWas = strlen($html);

        $inliner = new CssToInlineStyles();
        $html = $inliner->convert($html, $css);

        $lengthNow = strlen($html);

        Event::dispatch(new PostCssInlineEvent($html, $this));

        $this->debug('html_conversion_finished');
        $this->debug('html_size:'.$lengthWas.','.$lengthNow);

        return $html;
    }

    /**
     * Extract and return CSS from the given HTML
     */
    public function parseCssFromHtml(string &$html): string
    {
        $this->debug('parsing_css_from_html_started');

        $raw = [];
        $lengthWas = strlen($html);

        $html = preg_replace_callback(
            pattern: '/<style[^>]*>(.+?)<\/style>/s',
            callback: function (array $matches) use (&$raw) {
                $raw[] = $css = $matches[1];

                $this->debug('style_element_extracted_total_characters:'.strlen($css));

                if ($this->cssRemovalFromHtmlContentEnabled()) {
                    $this->debug('style_element_removed_from_html');

                    return '';
                }

                $this->debug('style_element_retained_in_html');

                return $matches[0];
            },
            subject: $html,
        );

        $html = (string) preg_replace_callback(
            pattern: [
                '/<link[^>]+href="([^"]+)"[^>]+rel="stylesheet"[^>]*>/',
                '/<link[^>]+rel="stylesheet"[^>]+href="([^"]+)"[^>]*>/',
            ],
            callback: function (array $matches) use (&$raw) {
                $file = $matches[1];
                $this->debug('link_stylesheet_element_extracted_path:'.$file);

                $raw[] = $css = $this->readCssFileAsString($file);
                $this->debug('link_stylesheet_resolved_total_characters:'.strlen($css));

                if ($this->cssRemovalFromHtmlContentEnabled()) {
                    $this->debug('link_stylesheet_element_removed_from_html');

                    return '';
                }

                $this->debug('link_stylesheet_element_retained_in_html');

                return $matches[0];
            },
            subject: $html ?? '',
        );

        $this->debug('parsed_css_total_elements:'.count($raw));

        $css = implode("\n\n", $raw);
        $lengthNow = strlen($html);

        $this->debug('parsing_css_from_html_finished');
        $this->debug('html_size:'.$lengthWas.','.$lengthNow);

        return $css;
    }

    public function beforeConvertingEmail(callable $callback): self
    {
        $this->debug('registered_callback_before_converting_email');
        Event::listen(PreEmailCssInlineEvent::class, fn (PreEmailCssInlineEvent $event) => $callback($event));

        return $this;
    }

    public function afterConvertingEmail(callable $callback): self
    {
        $this->debug('registered_callback_after_converting_email');
        Event::listen(PostEmailCssInlineEvent::class, fn (PostEmailCssInlineEvent $event) => $callback($event));

        return $this;
    }

    public function beforeConvertingHtml(callable $callback): self
    {
        $this->debug('registered_callback_before_converting_html');
        Event::listen(PreCssInlineEvent::class, fn (PreCssInlineEvent $event) => $callback($event));

        return $this;
    }

    public function afterConvertingHtml(callable $callback): self
    {
        $this->debug('registered_callback_after_converting_html');
        Event::listen(PostCssInlineEvent::class, fn (PostCssInlineEvent $event) => $callback($event));

        return $this;
    }

    /**
     * Provides an interface for you to return the singleton
     * instance from the facade
     *
     * Example:
     *      CssInline::instance()->blah()->blah()->blah();
     */
    public function instance(): self
    {
        return $this;
    }

    /**
     * Get the singleton instance from Laravel's Container
     */
    public static function singleton(): self
    {
        return app(CssInliner::class); /** @phpstan-ignore-line */
    }

    /**
     * Get all registered CSS files
     *
     * @return array<string,string>
     */
    public function cssFiles(): array
    {
        return $this->cssFiles;
    }

    /**
     * Get all registered raw CSS strings
     *
     * @return array<int,string>
     */
    public function cssRaw(): array
    {
        return $this->cssRaw;
    }

    /**
     * Halt the conversion of CSS to inline styles
     *
     * @return false
     */
    public function halt(): bool
    {
        $this->process = false;

        return false;
    }
}
