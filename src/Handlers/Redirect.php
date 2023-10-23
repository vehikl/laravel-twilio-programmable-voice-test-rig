<?php

namespace Vehikl\LaravelTwilioProgrammableVoiceTestRig\Handlers;

class Redirect extends Element
{
    public function isActionable(): bool
    {
        return true;
    }

    public function runAction(Callable $nextAction): bool
    {
        $uri = $this->element->textContent;
        $method = strtoupper($this->attr('method', 'POST'));
        $nextAction('Redirect', $uri, $method);
        return true;
    }
}

