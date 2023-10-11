<?php

namespace Vehikl\LaravelTwilioProgrammableVoiceTestRig\Handlers;

use SimpleXMLElement;
use Vehikl\LaravelTwilioProgrammableVoiceTestRig\ProgrammableVoiceRig;

class Hangup implements TwimlHandler
{
    public function handle(ProgrammableVoiceRig $programmableVoice, SimpleXMLElement $element, Callable $nextAction): bool
    {
        $programmableVoice->setCallStatus('completed');
        return true;
    }
}

