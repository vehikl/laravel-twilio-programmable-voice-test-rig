<?php

namespace Vehikl\LaravelTwilioProgrammableVoiceTestRig\Handlers;

use Closure;
use Vehikl\LaravelTwilioProgrammableVoiceTestRig\ProgrammableVoiceRig;


class Gather extends Element
{
    public function handle(Closure $next): ProgrammableVoiceRig
    {
        if (!$this->attr('action')) {
            $this->rig->warn('Detected gather without an action, which falls back to the current document. This can result in unexpected loops.');
        }
        $action = $this->attr('action', $this->rig->request?->fullUrl());
        $method = strtoupper($this->attr('method', 'POST'));

        $input = $this->rig->consumeInput('gather');

        if ($this->attr('actionOnEmptyResult', 'false') === 'false' && !$input) {
            return $next();
        }

        return $this->rig->navigate($action, $method, $input ?? [], 'Gather');
    }
}

