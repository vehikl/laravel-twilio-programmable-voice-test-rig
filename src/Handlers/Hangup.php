<?php

namespace Vehikl\LaravelTwilioProgrammableVoiceTestRig\Handlers;

use Closure;
use Exception;

class Hangup extends Element
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
        $this->rig->setCallStatus('completed');
        return true;
    }
}

