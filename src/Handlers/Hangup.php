<?php

namespace Vehikl\LaravelTwilioProgrammableVoiceTestRig\Handlers;

use DOMElement;
use Vehikl\LaravelTwilioProgrammableVoiceTestRig\CallStatus;

class Hangup extends Element
{
    public function callStatus(CallStatus $previous): CallStatus
    {
        return CallStatus::completed;
    }

    public function nextElement(): ?DOMElement
    {
        return null;
    }
}

