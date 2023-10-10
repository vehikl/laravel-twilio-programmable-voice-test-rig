<?php

namespace Vehikl\LaravelTwilioProgrammableVoiceTestRig\Handlers;

use SimpleXMLElement;
use Vehikl\LaravelTwilioProgrammableVoiceTestRig\ProgrammableVoiceRig;

class Hangup implements TwimlElement
{
    public function handle(ProgrammableVoiceRig $programmableVoice, SimpleXMLElement $element): bool
    {
        $programmableVoice->setCallStatus('completed');
        return true;
    }
}

