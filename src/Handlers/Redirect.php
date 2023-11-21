<?php

namespace Vehikl\LaravelTwilioProgrammableVoiceTestRig\Handlers;

use DOMElement;
use Vehikl\LaravelTwilioProgrammableVoiceTestRig\CallStatus;

class Redirect extends Element
{
    public function callStatus(CallStatus $previous): CallStatus
    {
        return $previous;
    }

    public function nextElement(): ?DOMElement
    {
        return null;
    }
}

