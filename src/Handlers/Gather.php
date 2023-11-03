<?php

namespace Vehikl\LaravelTwilioProgrammableVoiceTestRig\Handlers;

use Closure;
use Exception;


class Gather extends Element
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
            $this->rig->warn('Detected gather without an action, which falls back to the current document. This can result in unexpected loops.');
        }
        $action = $this->attr('action', $this->rig->request?->fullUrl());
        $method = strtoupper($this->attr('method', 'POST'));

        $input = $this->rig->consumeInput('gather');

        if ($this->attr('actionOnEmptyResult', 'false') === 'false' && !$input) {
            return false;
        }

        $nextAction('Gather', $action, $method, $input ?? []);
        return true;
    }
}


