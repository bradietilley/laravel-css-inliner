<?php

namespace LaravelCssInliner\Events;

use LaravelCssInliner\CssInliner;

/**
 * Event is fired before an HTML string is converted from CSS to inline styles.
 * Will also be fired for when an Email is converted too.
 */
class PreCssInlineEvent
{
    public function __construct(public string $html, public CssInliner $cssInliner)
    {
    }
}
