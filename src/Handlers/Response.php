<?php

namespace Vehikl\LaravelTwilioProgrammableVoiceTestRig\Handlers;

use DOMElement;
use Vehikl\LaravelTwilioProgrammableVoiceTestRig\CallStatus;

class Response extends Element
{
    public function callStatus(CallStatus $previous): CallStatus
    {
        return $previous;
    }

    public function nextElement(): ?DOMElement
    {
        return $this->element->firstElementChild;
    }
}
