<?php

namespace Vehikl\LaravelTwilioProgrammableVoiceTestRig\Handlers;

class Record extends Element
{
    public function isActionable(): bool
    {
        return true;
    }

    public function runAction(Callable $nextAction): bool
    {
        if (!$this->attr('action')) {
            $this->rig->warn('Detected record without an action, which falls back to the current document. This can result in unexpected loops.');
        }
        $action = $this->attr('action', $this->rig->request?->fullUrl());
        $input = $this->rig->consumeInput('record');
        if (!$input) {
            return false;
        }

        $method = strtoupper($this->attr('method', 'POST'));

        $nextAction('Record', $action, $method, $input);
        return true;
    }
}


