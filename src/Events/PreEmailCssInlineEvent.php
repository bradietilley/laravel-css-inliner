<?php

namespace BradieTilley\LaravelCssInliner\Events;

use BradieTilley\LaravelCssInliner\CssInliner;
use Symfony\Component\Mime\Email;

/**
 * Event is fired before an Email is converted from CSS to inline styles
 */
class PreEmailCssInlineEvent
{
    public function __construct(public Email $email, public CssInliner $cssInliner)
    {
    }
}
