<?php

namespace Vehikl\LaravelTwilioProgrammableVoiceTestRig\Handlers;

use Closure;

class Redirect extends Element
{
    public function isActionable(): bool
    {
        return true;
    }

    /**
     * @param Closure(string, string, string, array):void $nextAction
     */
    public function runAction(Closure $nextAction): bool
    {
        $uri = $this->element->textContent;
        $method = strtoupper($this->attr('method', 'POST'));
        $nextAction('Redirect', $uri, $method);
        return true;
    }
}

