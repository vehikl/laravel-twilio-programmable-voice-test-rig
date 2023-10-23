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
        $nextAction('Redirect', (string)$this->element, $this->element['method'] ?? 'POST');
        return true;
    }
}

