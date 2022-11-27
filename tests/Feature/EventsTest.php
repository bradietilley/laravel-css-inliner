<?php

declare(strict_types=1);

use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Event;
use LaravelCssInliner\CssInliner;
use LaravelCssInliner\Events\PostCssInlineEvent;
use LaravelCssInliner\Events\PostEmailCssInlineEvent;
use LaravelCssInliner\Events\PreCssInlineEvent;
use LaravelCssInliner\Events\PreEmailCssInlineEvent;
use LaravelCssInliner\Facades\CssInline;
use Symfony\Component\Mime\Email;

it('will fire events when converting HTML', function () {
    $css = '.font-bold { font-weight: bold; }';
    $html = 'Test<span class="font-bold">Test</span>Test';
    $final = 'Test<span class="font-bold" style="font-weight: bold;">Test</span>Test';

    $callbacks = [
        PreEmailCssInlineEvent::class => 0,
        PreCssInlineEvent::class => 0,
        PostCssInlineEvent::class => 0,
        PostEmailCssInlineEvent::class => 0,
    ];

    CssInliner::create()
        ->addCssRaw($css)
        ->beforeConvertingHtml(function (string $eventHtml, CssInliner $eventInliner, PreCssInlineEvent $event) use ($html, $css, &$callbacks) {
            $callbacks[PreCssInlineEvent::class]++;

            /* CSS looks good */
            expect($event->cssInliner->cssRaw())->toContain($css);
            expect($event->cssInliner->cssRaw())->toHaveCount(1);

            /* HTML looks good */
            expect($event->html)->sameHtml($html);
        })
        ->afterConvertingHtml(function (string $eventHtml, CssInliner $eventInliner, PostCssInlineEvent $event) use ($final, $css, &$callbacks) {
            $callbacks[PostCssInlineEvent::class]++;

            /* CSS looks good */
            expect($event->cssInliner->cssRaw())->toContain($css);
            expect($event->cssInliner->cssRaw())->toHaveCount(1);

            /* HTML looks good */
            expect($event->html)->sameHtml($final);
        })
        ->convert($html);

    expect($callbacks[PreEmailCssInlineEvent::class])->toBe(0)
        ->and($callbacks[PreCssInlineEvent::class])->toBe(1)
        ->and($callbacks[PostCssInlineEvent::class])->toBe(1)
        ->and($callbacks[PostEmailCssInlineEvent::class])->toBe(0)
        ->and('html_conversion_finished')->debugLogExists();
});

it('will fire events when converting an email', function () {
    $email = new Email();
    $email->html($html = 'Test<span class="font-bold">Test</span>Test');

    $final = 'Test<span class="font-bold" style="font-weight: bold;">Test</span>Test';
    $callbacks = [
        PreEmailCssInlineEvent::class => 0,
        PreCssInlineEvent::class => 0,
        PostCssInlineEvent::class => 0,
        PostEmailCssInlineEvent::class => 0,
    ];

    CssInliner::create()
        ->addCssRaw($css = '.font-bold { font-weight: bold; }')
        ->beforeConvertingEmail(function (Email $eventEmail, CssInliner $eventInliner, PreEmailCssInlineEvent $event) use ($html, $email, $css, &$callbacks) {
            $callbacks[PreEmailCssInlineEvent::class]++;

            /* CSS looks good */
            expect($event->cssInliner->cssRaw())->toContain($css);
            expect($event->cssInliner->cssRaw())->toHaveCount(1);

            /* HTML looks good */
            expect($event->email->getHtmlBody())->sameHtml($html);

            /* Email is same entity */
            expect($event->email)->toBe($email);
        })
        ->beforeConvertingHtml(function (string $eventHtml, CssInliner $eventInliner, PreCssInlineEvent $event) use ($html, $css, &$callbacks) {
            $callbacks[PreCssInlineEvent::class]++;

            /* CSS looks good */
            expect($event->cssInliner->cssRaw())->toContain($css);
            expect($event->cssInliner->cssRaw())->toHaveCount(1);

            /* HTML looks good */
            expect($event->html)->sameHtml($html);
        })
        ->afterConvertingHtml(function (string $eventHtml, CssInliner $eventInliner, PostCssInlineEvent $event) use ($final, $css, &$callbacks) {
            $callbacks[PostCssInlineEvent::class]++;

            /* CSS looks good */
            expect($event->cssInliner->cssRaw())->toContain($css);
            expect($event->cssInliner->cssRaw())->toHaveCount(1);

            /* HTML looks good */
            expect($event->html)->sameHtml($final);
        })
        ->afterConvertingEmail(function (Email $eventEmail, CssInliner $eventInliner, PostEmailCssInlineEvent $event) use ($final, $email, $css, &$callbacks) {
            $callbacks[PostEmailCssInlineEvent::class]++;

            /* CSS looks good */
            expect($event->cssInliner->cssRaw())->toContain($css);
            expect($event->cssInliner->cssRaw())->toHaveCount(1);

            /* HTML looks good */
            expect($event->email->getHtmlBody())->sameHtml($final);

            /* Email is same entity */
            expect($event->email)->toBe($email);
        })
        ->convertEmail($email);

    expect($callbacks[PreEmailCssInlineEvent::class])->toBe(1)
        ->and($callbacks[PreCssInlineEvent::class])->toBe(1)
        ->and($callbacks[PostCssInlineEvent::class])->toBe(1)
        ->and($callbacks[PostEmailCssInlineEvent::class])->toBe(1)
        ->and('email_conversion_finished')->debugLogExists();
});

it('will fire events to multiple listeners when converting an email', function () {
    $email = new Email();
    $email->html($html = 'Test<span class="font-bold">Test</span>Test');

    $final = 'Test<span class="font-bold" style="font-weight: bold;">Test</span>Test';
    $callbacks = [
        PreEmailCssInlineEvent::class => 0,
        PreCssInlineEvent::class => 0,
        PostCssInlineEvent::class => 0,
        PostEmailCssInlineEvent::class => 0,
    ];

    CssInliner::create()
        ->beforeConvertingEmail(function (Email $eventEmail, CssInliner $eventInliner, PreEmailCssInlineEvent $event) use (&$callbacks) {
            $callbacks[PreEmailCssInlineEvent::class]++;
        })
        ->beforeConvertingEmail(function (Email $eventEmail, CssInliner $eventInliner, PreEmailCssInlineEvent $event) use (&$callbacks) {
            $callbacks[PreEmailCssInlineEvent::class]++;
        })
        ->beforeConvertingHtml(function (string $eventHtml, CssInliner $eventInliner, PreCssInlineEvent $event) use (&$callbacks) {
            $callbacks[PreCssInlineEvent::class]++;
        })
        ->beforeConvertingHtml(function (string $eventHtml, CssInliner $eventInliner, PreCssInlineEvent $event) use (&$callbacks) {
            $callbacks[PreCssInlineEvent::class]++;
        })
        ->afterConvertingHtml(function (string $eventHtml, CssInliner $eventInliner, PostCssInlineEvent $event) use (&$callbacks) {
            $callbacks[PostCssInlineEvent::class]++;
        })
        ->afterConvertingHtml(function (string $eventHtml, CssInliner $eventInliner, PostCssInlineEvent $event) use (&$callbacks) {
            $callbacks[PostCssInlineEvent::class]++;
        })
        ->afterConvertingEmail(function (Email $eventEmail, CssInliner $eventInliner, PostEmailCssInlineEvent $event) use (&$callbacks) {
            $callbacks[PostEmailCssInlineEvent::class]++;
        })
        ->afterConvertingEmail(function (Email $eventEmail, CssInliner $eventInliner, PostEmailCssInlineEvent $event) use (&$callbacks) {
            $callbacks[PostEmailCssInlineEvent::class]++;
        })
        ->convertEmail($email);

    expect($callbacks[PreEmailCssInlineEvent::class])->toBe(2)
        ->and($callbacks[PreCssInlineEvent::class])->toBe(2)
        ->and($callbacks[PostCssInlineEvent::class])->toBe(2)
        ->and($callbacks[PostEmailCssInlineEvent::class])->toBe(2)
        ->and('email_conversion_finished')->debugLogExists();
});

it('will allow modification of html during pre and post events', function () {
    $css = '.font-bold { font-weight: bold; }';
    $html = 'Test<span class="font-bold">Test</span>Test';

    $expect = 'Test<span class="font-bold" style="font-weight: bold;">Test</span>Test';
    $actual = CssInliner::create()
        ->beforeConvertingHtml(fn (string & $eventHtml, CssInliner $eventInliner, PreCssInlineEvent $event) => $eventHtml .= 'something1')
        ->beforeConvertingHtml(fn (string & $eventHtml, CssInliner $eventInliner, PreCssInlineEvent $event) => $eventInliner->debug('ran_second_event'))
        ->afterConvertingHtml(fn (string & $eventHtml, CssInliner $eventInliner, PostCssInlineEvent $event) => $eventHtml .= 'something2')
        ->addCssRaw($css)
        ->convert($html);

    expect($actual)->toContain('something1');
    expect($actual)->toContain('something2');

    $actual = str_replace(
        search: [
            'something1',
            'something2',
        ],
        replace: '',
        subject: $actual,
    );

    expect($actual)->sameHtml($expect)
        ->and('html_conversion_finished')->debugLogExists()
        ->and('ran_second_event')->debugLogExists();
});

it('will allow halting of html conversion by halting css inliner', function () {
    $css = '.font-bold { font-weight: bold; }';
    $html = 'Test<span class="font-bold">Test</span>Test';

    $expect = $html;
    $actual = CssInliner::create()
        ->beforeConvertingHtml(fn (string $eventHtml, CssInliner $eventInliner, PreCssInlineEvent $event) => $eventInliner->halt())
        ->beforeConvertingHtml(fn (string $eventHtml, CssInliner $eventInliner, PreCssInlineEvent $event) => $eventInliner->debug('ran_second_event'))
        ->addCssRaw($css)
        ->convert($html);

    // No change
    expect($actual)->sameHtml($expect)
        ->and('html_processing_has_been_halted_skipping_conversion')->debugLogExists()
        ->and('email_processing_has_been_halted_skipping_conversion')->debugLogNotExists()
        ->and('ran_second_event')->debugLogNotExists()
        ->and('email_conversion_finished')->debugLogNotExists();
});

it('will allow halting of email conversion by halting css inliner', function () {
    $css = '.font-bold { font-weight: bold; }';
    $html = 'Test<span class="font-bold">Test</span>Test';

    $email = new Email();
    $email->html($html = 'Test<span class="font-bold">Test</span>Test');

    $expect = $html;

    $actual = CssInliner::create()
        ->beforeConvertingEmail(fn (Email $eventEmail, CssInliner $eventInliner, PreEmailCssInlineEvent $event) => $eventInliner->halt())
        ->beforeConvertingHtml(fn (Email $eventEmail, CssInliner $eventInliner, PreEmailCssInlineEvent $event) => $eventInliner->debug('ran_second_event'))
        ->addCssRaw($css)
        ->convertEmail($email);

    // No change
    expect($actual->getHtmlBody())->sameHtml($expect)
        ->and('email_processing_has_been_halted_skipping_conversion')->debugLogExists()
        ->and('html_processing_has_been_halted_skipping_conversion')->debugLogNotExists()
        ->and('ran_second_event')->debugLogNotExists()
        ->and('email_conversion_finished')->debugLogNotExists();
});

it('listens to laravel mail sending event', function () {
    $css = '.font-bold { font-weight: bold; }';
    $email = new Email();
    $email->html($html = 'Test<span class="font-bold">Test</span>Test');

    $expect = 'Test<span class="font-bold" style="font-weight: bold;">Test</span>Test';

    CssInline::addCssRaw($css);

    $preEmail = null;
    $postEmail = null;
    CssInline::beforeConvertingEmail(function (Email $eventEmail, CssInliner $eventInliner, PreEmailCssInlineEvent $event) use (&$preEmail) {
        $preEmail = $event->email;
    });
    CssInline::afterConvertingEmail(function (Email $eventEmail, CssInliner $eventInliner, PostEmailCssInlineEvent $event) use (&$postEmail) {
        $postEmail = $event->email;
    });

    Event::dispatch(new MessageSending($email, []));

    expect($preEmail)->toBe($email)
        ->and($postEmail)->toBe($email)
        ->and($email->getHtmlBody())->sameHtml($expect)
        ->and('email_listener_is_disabled_skipping_conversion')->debugLogNotExists()
        ->and('email_conversion_finished')->debugLogExists();
});

it('listens to laravel mail sending event but ignores event if disabled', function () {
    $css = '.font-bold { font-weight: bold; }';
    $email = new Email();
    $email->html($html = 'Test<span class="font-bold">Test</span>Test');

    $expect = $html;

    CssInline::addCssRaw($css);

    $preEmail = null;
    $postEmail = null;
    CssInline::disableEmailListener();

    CssInline::beforeConvertingEmail(function (Email $eventEmail, CssInliner $eventInliner, PreEmailCssInlineEvent $event) use (&$preEmail) {
        $preEmail = $event->email;
    });
    CssInline::afterConvertingEmail(function (Email $eventEmail, CssInliner $eventInliner, PostEmailCssInlineEvent $event) use (&$postEmail) {
        $postEmail = $event->email;
    });

    Event::dispatch(new MessageSending($email, []));

    expect($preEmail)->toBeNull()
        ->and($postEmail)->toBeNull()
        ->and($email->getHtmlBody())->sameHtml($expect);

    expect('email_listener_is_disabled_skipping_conversion')->debugLogExists()
        ->and('email_conversion_finished')->debugLogNotExists();
});
