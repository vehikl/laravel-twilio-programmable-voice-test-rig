<?php

namespace Vehikl\LaravelTwilioProgrammableVoiceTestRig\Handlers;

use SimpleXMLElement;

class Dial implements TwimlHandler
{
    public function handle(ProgrammableVoiceRig $programmableVoice, SimpleXMLElement $element): bool
    {
        if (!isset($element['action'])) {
            return false;
        }
        $programmableVoice->setNextAction($element['method'] ?? 'POST', $element['action'], $programmableVoice->shiftDial());
        return true;
    }
}

