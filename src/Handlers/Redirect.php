<?php

namespace Vehikl\LaravelTwilioProgrammableVoiceTestRig\Handlers;

use Closure;
use DOMElement;
use Vehikl\LaravelTwilioProgrammableVoiceTestRig\CallStatus;
use Vehikl\LaravelTwilioProgrammableVoiceTestRig\ProgrammableVoiceRig;

class Redirect extends Element
{
    public function callStatus(CallStatus $previous): CallStatus
    {
        return $previous;
    }

    /**
     * @param Closure():ProgrammableVoiceRig $next
     */
    public function handle(Closure $next): ProgrammableVoiceRig
    {
        $uri = $this->element->textContent;
        $method = strtoupper($this->attr('method', 'POST'));
        if (!$uri) {
            return $next();
        }

        return $this->rig->navigate($uri, $method, [], 'Redirect');
    }

    public function nextElement(): ?DOMElement
    {
        return null;
    }
}

