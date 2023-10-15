<?php

namespace Vehikl\LaravelTwilioProgrammableVoiceTestRig\Handlers;

use Exception;
use SimpleXMLElement;
use Vehikl\LaravelTwilioProgrammableVoiceTestRig\ProgrammableVoiceRig;

class Gather extends TwimlElement
{
    protected ?array $gather = null;
    protected ?string $actionUri = null;

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
            throw new Exception('Unable to handle gather, action and request are both missing');
        }
        $method = strtoupper($this->element['method'] ?? 'POST');

        $input = $this->rig->consumeInput('gather');

        if (($this->element['actionOnEmptyResult'] ?? 'false') === 'false' || !$input) {
            return false;
        }

        $nextAction('Gather', $this->actionUri, $method, $input ?? []);
        return true;
    }
}


