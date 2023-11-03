<?php

namespace Vehikl\LaravelTwilioProgrammableVoiceTestRig\Handlers;

use Closure;

class Hangup extends Element
{
    public function isActionable(): bool
    {
        return true;
    }
    public function runAction(Closure $nextAction): bool
    {
        $this->rig->setCallStatus('completed');
        return true;
    }
}

