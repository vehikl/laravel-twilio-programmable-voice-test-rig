<?php

namespace Vehikl\LaravelTwilioProgrammableVoiceTestRig\Handlers;

use SimpleXMLElement;
use Vehikl\LaravelTwilioProgrammableVoiceTestRig\ProgrammableVoiceRig;

class Gather implements TwimlHandler
{
    public function handle(ProgrammableVoiceRig $programmableVoice, SimpleXMLElement $element): bool
    {
        $action = $element['action'] ?? $programmableVoice->currentUrl();
        $method = strtoupper($element['method'] ?? 'post');

        $input = $programmableVoice->shiftInput();
        if (($element['actionOnEmptyResult'] ?? 'false') === 'false' || !$input) {
            return false;
        }

        $programmableVoice->setNextAction(
            $method,
            $action,
            $input,
        );
        return true;
    }
}


