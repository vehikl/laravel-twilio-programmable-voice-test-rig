<?php

namespace Vehikl\LaravelTwilioProgrammableVoiceTestRig\Handlers;

use Exception;
use SimpleXMLElement;
use Vehikl\LaravelTwilioProgrammableVoiceTestRig\ProgrammableVoiceRig;

class Gather implements TwimlHandler
{
    public function handle(ProgrammableVoiceRig $programmableVoice, SimpleXMLElement $element, Callable $nextAction): bool
    {
        $action = $element['action'] ?? $programmableVoice->getRequest()?->fullUrl() ?? null;
        if (!$action) {
            throw new Exception('Unable to handle gather, action and request are both missing');
        }

        $method = strtoupper($element['method'] ?? 'POST');

        $input = $programmableVoice->shiftInput();
        if (($element['actionOnEmptyResult'] ?? 'false') === 'false' || !$input) {
            return false;
        }

        $nextAction($action, $method, $input);
        return true;
    }
}


