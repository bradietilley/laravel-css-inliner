# CSS Style Inliner for Laravel

## Install

Via Composer

```shell
composer require laravel-css-inliner/css-inliner
```

## Usage

Using Laravel's container:

```php
use LaravelCssInliner\CssInliner;

/** @var LaravelCssInliner\CssInliner $inliner */
$inliner = app(CssInliner::class);

$inliner->addCssPath(resource_path('css/email.css'));
$inliner->addCssRaw('.text-success { color: #00ff00; }');

# Convert your own HTML/CSS
$html = '<span class="text-success">Success text</span>';
$html = $inliner->convert($html);

echo $html; // <span class="text-success" style="color: #00ff00;">Success text</span>
```

Using the facade:

```php
use LaravelCssInliner\Facades\CssInline;

CssInliner::addCssPath(resource_path('css/email.css'));
CssInliner::addCssRaw('.text-success { color: #00ff00; }');

# Convert your own HTML/CSS
$html = '<span class="text-success">Success text</span>';
$html = CssInliner::convert($html);

echo $html; // <span class="text-success" style="color: #00ff00;">Success text</span>
```

Convert Laravel Mail:

```php
use Illuminate\Support\Facades\Mail;
use LaravelCssInliner\Facades\CssInline;

# You can disable the Laravel CSS Inliner for dispatched Mailable views
CssInline::disableEmailListener();

# You can re-enable the Laravel CSS Inliner for dispatched Mailable views (default is enabled)
CssInline::enableEmailListener();

# When enabled:
Mail::to('john@example.org')->send(new MyMailableWithCss());
// Example: ...<span class="font-bold" style="font-weight: bold;">bold</span>

# When disabled:
Mail::to('john@example.org')->send(new MyMailableWithCss());
// Example: ...<span class="font-bold">bold</span>
```

Read CSS from within the HTML/Email (instead of an externally registered stylesheet):

```php
use LaravelCssInliner\Facades\CssInline;

# You can disable this feature (default is disabled):
CssInline::disableCssExtractionFromHtmlContent();

# You can enable this feature:
CssInline::enableCssExtractionFromHtmlContent();

$html = '<head><style>...</style><link rel="stylesheet" href="..."></head><body>...</body>';
// CSS from the style/link elements will be used when this html is converted
CssInline::convert($html);
```

Listen to Events:

```php
use LaravelCssInliner\Facades\CssInline;

CssInline::beforeConvertingEmail(fn (PreEmailCssInlineEvent $event) => echo 'Do something before Email CSS is inlined');
CssInline::afterConvertingEmail(fn (PostEmailCssInlineEvent $event) => echo 'Do something after Email CSS is inlined');
CssInline::beforeConvertingHtml(fn (PreCssInlineEvent $event) => echo 'Do something before HTML CSS is inlined');
CssInline::afterConvertingHtml(fn (PostCssInlineEvent $event) => echo 'Do something after HTML CSS is inlined');

// Under the hood these four methods simply proxy to Event::listen(), which you can use too:

Event::listen(PreEmailCssInlineEvent::class, fn() => echo 'Do something before Email CSS is inlined');
Event::listen(PostEmailCssInlineEvent::class, fn() => echo 'Do something after Email CSS is inlined');
Event::listen(PreCssInlineEvent::class, fn() => echo 'Do something before HTML CSS is inlined');
Event::listen(PostCssInlineEvent::class, fn() => echo 'Do something after HTML CSS is inlined');
```

## Change log

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Testing

```shell
composer test
```

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.
