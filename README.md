# CSS Style Inliner for Laravel

![Static Analysis](https://github.com/bradietilley/css-inliner/actions/workflows/static.yml/badge.svg)
![Tests](https://github.com/bradietilley/css-inliner/actions/workflows/tests.yml/badge.svg)

## Overview

This package leverages `tijsverkoyen/css-to-inline-styles` to convert CSS classes in mailable views to inline styles, for improved email client compatibility.

You can either leverage the automation of this package, or opt to hook into this package's event (or even Laravel's core events) and manually choose what gets converted and with what stylesheets. See Usage section for more details.

## Install

Via Composer

```shell
composer require bradietilley/laravel-css-inliner
```

## Usage

For the purpose of this demonstration we'll use the facade `BradieTilley\LaravelCssInliner\Facades\CssInline`, however if you prefer directly using the instance (like myself) you can swap out `BradieTilley\LaravelCssInliner\Facades\CssInline::` for any of the below:

- `BradieTilley\LaravelCssInliner\Facades\CssInline::`
- `BradieTilley\LaravelCssInliner\CssInliner::singleton()->`
- `app(BradieTilley\LaravelCssInliner\CssInliner::class)->`
- `app()->make(BradieTilley\LaravelCssInliner\CssInliner::class)->`

#### Adding CSS via PHP

You can at any point (before HTML conversion) define your CSS files or raw CSS that you wish to add to every HTML string or email that is converted by the CSS Inliner. A good example of this is a base stylesheet that all emails should inherit.

```php
use BradieTilley\LaravelCssInliner\Facades\CssInline;

CssInline::addCssPath(resource_path('css/email.css'));
CssInline::addCssRaw('body { background: #eee; }');

# Or, you can achieve the same outcome by letting CssInline decide whether it's a path or raw CSS:
CssInline::addCss(resource_path('css/email.css'));
CssInline::addCss('body { background: #eee; }');
```

#### Manual conversion

You may wish to manually convert some CSS in an HTML string or Email.

```php
use BradieTilley\LaravelCssInliner\Facades\CssInline;
use Symfony\Component\Mime\Email;

CssInline::addCss('.font-bold { font-weight: bold; }');

# Convert an HTML string
$html = CssInline::convert('<html><body><div class="font-bold">Bold</div></body></html>');
echo $html; // <html><body><div class="font-bold" style="font-weight: bold;">Bold</div></body></html>

# Convert an email
$email = new Email();
$email->html('<html><body><div class="font-bold">Bold</div></body></html>');
CssInline::convertEmail($email);
echo $email->getHtmlBody(); // <html><body><div class="font-bold" style="font-weight: bold;">Bold</div></body></html>
```

#### Option: Automatically parse Laravel email (or don't automatically parse Laravel email)

You may wish to conditionally enable or disable the CSS Inliner for mail sent from Laravel (via `Mail::send()`). To do this, we can leverage the `emailListener` option. Default is `true` (and as such will automatically convert CSS classes found in your emails sent from Laravel).

```php
use BradieTilley\LaravelCssInliner\Facades\CssInline;

CssInline::emailListenerEnabled(); // Current state: true or false
CssInline::enableEmailListener(); // Enables option; returns instance of CssInliner
CssInline::disableEmailListener(); // Disables option; returns instance of CssInliner
```

#### Option: Read CSS (style and link elements) from within any of the given HTML or emails

You may wish to parse `<style>` or `<link>` stylesheets that are found within the HTML or email, for example if you want to store email-specific CSS within the email view itself. To do this, we can leverage the `cssFromHtmlContent` option. Default is `false`.

```php
use BradieTilley\LaravelCssInliner\Facades\CssInline;

CssInline::cssFromHtmlContentEnabled(); // Current state: true or false
CssInline::enableCssExtractionFromHtmlContent(); // Enables option; returns instance of CssInliner
CssInline::disableCssExtractionFromHtmlContent(); // Disables option; returns instance of CssInliner
```

#### Option: After reading CSS (from above), remove the original CSS (style and link elements) from the HTML or email

You may wish to strip out the large `<style>` or `<link>` stylesheets after this package converts the CSS to inline styles, to reduce the payload size of emails sent out from your system. To do this, we can leverage the `cssRemovalFromHtmlContent` option. Default is `false`.

```php
use BradieTilley\LaravelCssInliner\Facades\CssInline;

CssInline::cssRemovalFromHtmlContentEnabled(); // Current state: true or false
CssInline::enableCssRemovalFromHtmlContent(); // Enables option; returns instance of CssInliner
CssInline::disableCssRemovalFromHtmlContent(); // Disables option; returns instance of CssInliner
```

```php
use BradieTilley\LaravelCssInliner\Facades\CssInline;

CssInline::doSomething();
CssInliner::addCssPath(resource_path('css/email.css'));
CssInliner::addCssRaw('.text-success { color: #00ff00; }');

# Convert your own HTML/CSS
$html = '<span class="text-success">Success text</span>';
$html = CssInliner::convert($html);

echo $html; // <span class="text-success" style="color: #00ff00;">Success text</span>
```

### Events:

There are four events fired by CssInliner - two for HTML conversion, and all four for Email conversion. The order of which the events are called is:

- 1st: `BradieTilley\LaravelCssInliner\Events\PreEmailCssInlineEvent` (Email only)
- 2nd: `BradieTilley\LaravelCssInliner\Events\PreCssInlineEvent` (Email + HTML)
- 3rd: `BradieTilley\LaravelCssInliner\Events\PostCssInlineEvent` (Email + HTML)
- 4th: `BradieTilley\LaravelCssInliner\Events\PostEmailCssInlineEvent` (Email only)

Listening to events can be done through Laravel's normal means. For example:

```php
Event::listen(\BradieTilley\LaravelCssInliner\Events\PreEmailCssInlineEvent::class, fn () => doSomething());
```

Or, you may wish to hook into CSS Inliner using the callback methods: `beforeConvertingEmail`, `afterConvertingEmail`, `beforeConvertingHtml`, `afterConvertingHtml`. These methods accept a callback and are simply a proxy to `Event::listen()` so feel free to treat the callbacks used in the examples below as the second argument to `Event::listen()` of the corresponding CssInliner events. 

#### Event: Before Email is Converted (`beforeConvertingEmail`)

```php
CssInliner::beforeConvertingEmail(function (PreEmailCssInlineEvent $event) {
    # You have access to the unconverted-Email and CSS Inliner instance via the event
    $event->email; // instanceof: \Symfony\Component\Mime\Email
    $event->cssInliner; // instanceof BradieTilley\LaravelCssInliner\CssInliner
    echo $event->email->getHtmlBody(); // <html>...</html>

    # Because this is a 'before' event, you may choose to halt the conversion of this *one* Email
    return $event->cssInliner->halt();
    # Laravel will halt any other event listeners; CSS Inliner will return the Email immediately (and not convert it)
});
```

#### Event: Before HTML is Converted (`beforeConvertingHtml`)

```php
CssInliner::beforeConvertingHtml(function (PreCssInlineEvent $event) {
    # You have access to the unconverted-HTML and CSS Inliner instance via the event
    $event->html; // string
    $event->cssInliner; // instanceof BradieTilley\LaravelCssInliner\CssInliner
    echo $event->html; // <html>...</html>

    # Because this is a 'before' event, you may choose to halt the conversion of this *one* HTML string
    return $event->cssInliner->halt();
    # Laravel will halt any other event listeners; CSS Inliner will return the HTML immediately (and not convert it) 
});
```

#### Event: After HTML is Converted (`afterConvertingHtml`)

```php
CssInliner::afterConvertingHtml(function (PostCssInlineEvent $event) {
    # You have access to the converted-HTML and CSS Inliner instance via the event
    $event->html; // string
    $event->cssInliner; // instanceof BradieTilley\LaravelCssInliner\CssInliner
    echo $event->html; // <html>...</html>

    # Because this is an 'after' event, you cannot halt the conversion of the HTML string (unlike the 'before' event)
});
```

#### Event: After Email is Converted (`afterConvertingEmail`)

```php
CssInliner::afterConvertingEmail(function (PostEmailCssInlineEvent $event) {
    # You have access to the converted-Email and CSS Inliner instance via the event
    $event->email; // instanceof: \Symfony\Component\Mime\Email
    $event->cssInliner; // instanceof BradieTilley\LaravelCssInliner\CssInliner
    echo $event->email->getHtmlBody(); // <html>...</html>

    # Because this is an 'after' event, you cannot halt the conversion of the Email (unlike the 'before' event)
});
```

## Change log

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Testing

```shell
composer test
```

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.
