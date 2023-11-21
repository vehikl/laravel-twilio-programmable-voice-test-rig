<?php

namespace Vehikl\LaravelTwilioProgrammableVoiceTestRig\Handlers;

use Closure;
use DOMElement;
use Vehikl\LaravelTwilioProgrammableVoiceTestRig\ProgrammableVoiceRig;

class Record extends Element
{
    /**
     * @param Closure():ProgrammableVoiceRig $next
     */
    public function handle(Closure $next): ProgrammableVoiceRig
    {
        if (!$this->attr('action')) {
            $this->rig->warn('Detected record without an action, which falls back to the current document. This can result in unexpected loops.');
            var_dump('RECORD.NO ACTION');
        }
        $action = $this->attr('action', $this->rig->request?->fullUrl());
        $input = $this->rig->consumeInput('record');
        if (!$input) {
            var_dump('RECORD.NO INPUT');
            return $next();
        }

        $method = strtoupper($this->attr('method', 'POST'));

        var_dump('RECORD.NAVIGATE', [$method, $action]);
        return $this->rig->navigate($action, $method, $input, 'Record');
    }

    public function nextElement(): ?DOMElement
    {
        return $this->element->nextElementSibling;
    }
}

