<?php

namespace Vehikl\LaravelTwilioProgrammableVoiceTestRig\Handlers;

use SimpleXMLElement;
use Vehikl\LaravelTwilioProgrammableVoiceTestRig\ProgrammableVoiceRig;

class Redirect implements TwimlHandler
{
    public function handle(ProgrammableVoiceRig $programmableVoice, SimpleXMLElement $element, Callable $nextAction): bool
    {
        $nextAction((string)$element, $element['method'] ?? 'POST');
        return true;
    }
}

