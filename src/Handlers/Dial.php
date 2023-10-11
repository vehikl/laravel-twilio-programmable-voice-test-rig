<?php

namespace Vehikl\LaravelTwilioProgrammableVoiceTestRig\Handlers;

use SimpleXMLElement;
use Vehikl\LaravelTwilioProgrammableVoiceTestRig\ProgrammableVoiceRig;

class Dial implements TwimlHandler
{
    public function handle(ProgrammableVoiceRig $programmableVoice, SimpleXMLElement $element, Callable $nextAction): bool
    {
        if (!isset($element['action'])) {
            return false;
        }
        $dial = $programmableVoice->shiftDial();
        $nextAction($element['action'], $element['method'] ?? 'POST', $dial);
        return true;
    }
}

