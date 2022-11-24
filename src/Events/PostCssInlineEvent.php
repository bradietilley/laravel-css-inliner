<?php

namespace LaravelCssInliner\Events;

use LaravelCssInliner\CssInliner;

/**
 * Event is fired after an HTML string is converted from CSS to inline styles.
 * Will also be fired for when an Email is converted too.
 */
class PostCssInlineEvent
{
    public function __construct(public string $html, public CssInliner $cssInliner)
    {
    }
}
