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
        $action = $this->attr('action');
        if (!$action) {
            $this->rig->warn('No action');
            return false;
        }

        $input = $this->rig->consumeInput('dial');
        if (!$input) {
            $this->rig->warn('No input');
            return false;
        }

        $method = $this->attr('method', 'POST');
        $nextAction('Dial', $action, $method, $input);
        return true;
    }
}

