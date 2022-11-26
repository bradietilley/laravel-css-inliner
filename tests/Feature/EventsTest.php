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
        ->beforeConvertingHtml(function (PreCssInlineEvent $event) use ($html, $css, &$callbacks) {
            $callbacks[PreCssInlineEvent::class]++;

            /* CSS looks good */
            expect($event->cssInliner->cssRaw())->toContain($css);
            expect($event->cssInliner->cssRaw())->toHaveCount(1);

            /* HTML looks good */
            expect($event->html)->sameHtml($html);
        })
        ->afterConvertingHtml(function (PostCssInlineEvent $event) use ($final, $css, &$callbacks) {
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
        ->and($callbacks[PostEmailCssInlineEvent::class])->toBe(0);
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
        ->beforeConvertingEmail(function (PreEmailCssInlineEvent $event) use ($html, $email, $css, &$callbacks) {
            $callbacks[PreEmailCssInlineEvent::class]++;

            /* CSS looks good */
            expect($event->cssInliner->cssRaw())->toContain($css);
            expect($event->cssInliner->cssRaw())->toHaveCount(1);

            /* HTML looks good */
            expect($event->email->getHtmlBody())->sameHtml($html);

            /* Email is same entity */
            expect($event->email)->toBe($email);
        })
        ->beforeConvertingHtml(function (PreCssInlineEvent $event) use ($html, $css, &$callbacks) {
            $callbacks[PreCssInlineEvent::class]++;

            /* CSS looks good */
            expect($event->cssInliner->cssRaw())->toContain($css);
            expect($event->cssInliner->cssRaw())->toHaveCount(1);

            /* HTML looks good */
            expect($event->html)->sameHtml($html);
        })
        ->afterConvertingHtml(function (PostCssInlineEvent $event) use ($final, $css, &$callbacks) {
            $callbacks[PostCssInlineEvent::class]++;

            /* CSS looks good */
            expect($event->cssInliner->cssRaw())->toContain($css);
            expect($event->cssInliner->cssRaw())->toHaveCount(1);

            /* HTML looks good */
            expect($event->html)->sameHtml($final);
        })
        ->afterConvertingEmail(function (PostEmailCssInlineEvent $event) use ($final, $email, $css, &$callbacks) {
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
        ->and($callbacks[PostEmailCssInlineEvent::class])->toBe(1);
});

it('will allow modification of html during pre and post events', function () {
    $css = '.font-bold { font-weight: bold; }';
    $html = 'Test<span class="font-bold">Test</span>Test';

    $expect = 'Test<span class="font-bold" style="font-weight: bold;">Test</span>Test';
    $actual = CssInliner::create()
        ->beforeConvertingHtml(fn (PreCssInlineEvent $event) => $event->html .= 'something1')
        ->afterConvertingHtml(fn (PostCssInlineEvent $event) => $event->html .= 'something2')
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

    expect($actual)->sameHtml($expect);
});

it('will allow halting of html conversion by halting css inliner', function () {
    $css = '.font-bold { font-weight: bold; }';
    $html = 'Test<span class="font-bold">Test</span>Test';

    $expect = $html;
    $actual = CssInliner::create()
        ->beforeConvertingHtml(fn (PreCssInlineEvent $event) => $event->cssInliner->halt())
        ->addCssRaw($css)
        ->convert($html);

    // No change
    expect($actual)->sameHtml($expect);
});

it('will allow halting of email conversion by halting css inliner', function () {
    $css = '.font-bold { font-weight: bold; }';
    $html = 'Test<span class="font-bold">Test</span>Test';

    $email = new Email();
    $email->html($html = 'Test<span class="font-bold">Test</span>Test');

    $expect = $html;

    $actual = CssInliner::create()
        ->beforeConvertingEmail(fn (PreEmailCssInlineEvent $event) => $event->cssInliner->halt())
        ->addCssRaw($css)
        ->convertEmail($email);

    // No change
    expect($actual->getHtmlBody())->sameHtml($expect);
});

it('listens to laravel mail sending event', function () {
    $css = '.font-bold { font-weight: bold; }';
    $email = new Email();
    $email->html($html = 'Test<span class="font-bold">Test</span>Test');

    $expect = 'Test<span class="font-bold" style="font-weight: bold;">Test</span>Test';

    CssInline::addCssRaw($css);

    $preEmail = null;
    $postEmail = null;
    CssInline::beforeConvertingEmail(function (PreEmailCssInlineEvent $event) use (&$preEmail) {
        $preEmail = $event->email;
    });
    CssInline::afterConvertingEmail(function (PostEmailCssInlineEvent $event) use (&$postEmail) {
        $postEmail = $event->email;
    });

    Event::dispatch(new MessageSending($email, []));

    expect($preEmail)->toBe($email)
        ->and($postEmail)->toBe($email)
        ->and($email->getHtmlBody())->sameHtml($expect);
});
