<?php

namespace Vehikl\LaravelTwilioProgrammableVoiceTestRig\Handlers;

use Vehikl\LaravelTwilioProgrammableVoiceTestRig\CallStatus;

class Pause extends Element
{
    public function callStatus(CallStatus $previous): CallStatus
    {
        if ($previous === CallStatus::ringing && !$this->element->previousElementSibling) {
            return $previous;
        }

        return parent::callStatus($previous);
    }
}

