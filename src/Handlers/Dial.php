<?php

namespace Vehikl\LaravelTwilioProgrammableVoiceTestRig\Handlers;

use Closure;
use Exception;

class Dial extends Element
{
    public function isActionable(): bool
    {
        return true;
    }

    /**
     * @param Closure(string, string, string, array):void $nextAction
     * @throws Exception
     */
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

