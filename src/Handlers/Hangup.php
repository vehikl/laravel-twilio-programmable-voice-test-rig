<?php

namespace Vehikl\LaravelTwilioProgrammableVoiceTestRig\Handlers;

use Closure;
use DOMElement;
use Vehikl\LaravelTwilioProgrammableVoiceTestRig\ProgrammableVoiceRig;

class Hangup extends Element
{
    /**
     * @param Closure():ProgrammableVoiceRig $next
     */
    public function handle(Closure $next): ProgrammableVoiceRig
    {
        $this->rig->setCallStatus('completed');
        return $this->rig;
    }

    public function nextElement(): ?DOMElement
    {
        return null;
    }
}

