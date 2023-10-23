<?php

namespace Vehikl\LaravelTwilioProgrammableVoiceTestRig\Handlers;

use Exception;
use SimpleXMLElement;
use Vehikl\LaravelTwilioProgrammableVoiceTestRig\ProgrammableVoiceRig;

class Record extends Element
{
    protected ?string $actionUri;

    public function __construct(ProgrammableVoiceRig $rig, SimpleXMLElement $element, ?TwimlElement $parent = null)
    {
        parent::__construct($rig, $element, $parent);
        $this->actionUri = $element['action'] ?? $rig->request?->fullUrl() ?? null;
    }

    public function isActionable(): bool
    {
        return true;
    }

    public function runAction(Callable $nextAction): bool
    {
        if (!$this->actionUri) {
            throw new Exception('Unable to handle record, action and request are both missing');
        }
        $input = $this->rig->consumeInput('record');
        if (!$input) {
            return false;
        }

        $method = strtoupper($this->element['method'] ?? 'post');

        $nextAction('Record', $this->actionUri, $method, $input);
        return true;
    }
}


