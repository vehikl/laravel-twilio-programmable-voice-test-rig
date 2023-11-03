<?php

namespace Vehikl\LaravelTwilioProgrammableVoiceTestRig\Handlers;

use Closure;

class Dial extends Element
{
    public function isActionable(): bool
    {
        return true;
    }

    public function runAction(Closure $nextAction): bool
    {
        $action = $this->attr('action');
        if (!$action) {
            return false;
        }

        $input = $this->rig->consumeInput('dial');
        if (!$input) {
            return false;
        }

        $method = $this->attr('method', 'POST');
        $nextAction('Dial', $action, $method, $input);
        return true;
    }
}

