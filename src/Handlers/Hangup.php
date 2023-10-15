<?php

namespace Vehikl\LaravelTwilioProgrammableVoiceTestRig\Handlers;

class Hangup extends TwimlElement
{
    public function isActionable(): bool
    {
        return true;
    }
    public function runAction(Callable $nextAction): bool
    {
        $this->rig->setCallStatus('completed');
        return true;
    }
}

