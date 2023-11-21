<?php

namespace Vehikl\LaravelTwilioProgrammableVoiceTestRig\Handlers;

use Closure;
use DOMElement;
use PHPUnit\Framework\Assert;
use Vehikl\LaravelTwilioProgrammableVoiceTestRig\CallStatus;
use Vehikl\LaravelTwilioProgrammableVoiceTestRig\ProgrammableVoiceRig;

class Reject extends Element
{
    public function callStatus(CallStatus $previous): CallStatus
    {
        Assert::assertNotEquals(CallStatus::in_progress, $previous, 'Cannot reject a call that has been interacted with');

        return $this->attr('reason') === 'busy'
            ? CallStatus::busy
            : CallStatus::no_answer;
    }

    /**
     * @param Closure():ProgrammableVoiceRig $next
     */
    public function handle(Closure $next): ProgrammableVoiceRig
    {
        return $this->rig;
    }

    public function nextElement(): ?DOMElement
    {
        return null;
    }
}

