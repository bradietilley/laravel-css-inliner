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

$inliner = app(CssInliner::class);

$inliner->addCss(resource_path('css/email.css'));
$inliner->addCssRaw('.text-green-500 { color: #0f0; }');

# Convert your own HTML/CSS
$html = '<span class="text-green-500">Green text</span>';
$html = $inliner->convert($html);
echo $html; // <span class="text-green-500" style="color: #00ff00;">Green text</span>
```

Using the facade:

```php
use LaravelCssInliner\Facades\CssInline;

CssInline::addCss(resource_path('css/email.css'));
CssInline::addCssRaw('.text-green-500 { color: #00ff00; }');

# Convert your own HTML/CSS
$html = '<span class="text-green-500">Green text</span>';
$html = CssInline::convert($html);
echo $html; // <span class="text-green-500" style="color: #00ff00;">Green text</span>
```

Convert Laravel Mail:

```php
use Illuminate\Support\Facades\Mail;
use LaravelCssInliner\Facades\CssInline;

# You can disable the Laravel CSS Inliner:
CssInline::disableEmailListener();

# Then re-enable it whenever (default is enabled):
CssInline::enableEmailListener();

# Enabled:
Mail::to('john@example.org')->send(new MyMailableWithCss());
// Example: ...<span class="font-bold" style="font-weight: bold;">bold</span>

# Disabled:
Mail::to('john@example.org')->send(new MyMailableWithCss());
// Example: ...<span class="font-bold">bold</span>
```

Read CSS from within the HTML/Email (instead of an externally registered stylesheet):

```php
use LaravelCssInliner\Facades\CssInline;

# Then enable this feature:
CssInliner::enableCssExtractionFromHtmlContent();
# Then disable it whenever (default is disabled):
CssInliner::disableCssExtractionFromHtmlContent();

```

Listen to Events:

```php
use LaravelCssInliner\Facades\CssInline;

CssInline::beforeConvertingEmail(fn (PreEmailCssInlineEvent $event) => echo 'Do something before Email CSS is inlined');
CssInline::afterConvertingEmail(fn (PostEmailCssInlineEvent $event) => echo 'Do something after Email CSS is inlined');
CssInline::beforeConvertingHtml(fn (PreCssInlineEvent $event) => echo 'Do something before HTML CSS is inlined');
CssInline::afterConvertingHtml(fn (PostCssInlineEvent $event) => echo 'Do something after HTML CSS is inlined');

// Or with Laravel's Event listen:

Event::listen(PreEmailCssInlineEvent::class, fn() => doSomething());
Event::listen(PostEmailCssInlineEvent::class, fn() => doSomething());
Event::listen(PreCssInlineEvent::class, fn() => doSomething());
Event::listen(PostCssInlineEvent::class, fn() => doSomething());
```

## Change log

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Testing

```shell
composer test
```

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.
