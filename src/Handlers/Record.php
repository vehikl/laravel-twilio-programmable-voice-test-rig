<?php

namespace Vehikl\LaravelTwilioProgrammableVoiceTestRig\Handlers;

use Closure;
use Exception;

class Record extends Element
{
    public function isActionable(): bool
    {
        return true;
    }

    /**
     * @param Closure(string, string, string, array):void $nextAction
     * @throws Exception
     */
    public function runAction(Closure $nextAction): bool
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


