<?php

namespace Vehikl\LaravelTwilioProgrammableVoiceTestRig\Handlers;

use Exception;
use SimpleXMLElement;
use Vehikl\LaravelTwilioProgrammableVoiceTestRig\ProgrammableVoiceRig;

class Record implements TwimlHandler
{
    public function handle(ProgrammableVoiceRig $programmableVoice, SimpleXMLElement $element, Callable $nextAction): bool
    {
        $action = $element['action'] ?? $programmableVoice->getRequest()?->fullUrl() ?? null;
        if (!$action) {
            throw new Exception('Unable to handle record, action and request are both missing');
        }
        $method = strtoupper($element['method'] ?? 'post');

        $input = $programmableVoice->consumeInput('record');
        if (!$input) {
            return false;
        }

        $nextAction($action, $method, $input);
        return true;
    }
}


