<?php

declare(strict_types=1);

namespace LaravelCssInliner;

use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use LaravelCssInliner\Facades\CssInline;

class LaravelCssInlinerServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->singleton(CssInliner::class);

        Event::listen(MessageSending::class, fn (MessageSending $event) => CssInline::convertEmail($event->message));
    }
}
