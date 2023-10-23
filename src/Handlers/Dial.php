<?php

namespace Vehikl\LaravelTwilioProgrammableVoiceTestRig\Handlers;

class Dial extends Element
{
    public function isActionable(): bool
    {
        return true;
    }

    public function runAction(Callable $nextAction): bool
    {
        if (!isset($this->element['action'])) {
            return false;
        }

        $input = $this->rig->consumeInput('dial');
        if (!$input) {
            return false;
        }

        $nextAction('Dial', $this->element['action'], $this->element['method'] ?? 'POST', $input);
        return true;
    }
}

