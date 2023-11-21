<?php

namespace Vehikl\LaravelTwilioProgrammableVoiceTestRig\Handlers;

use DOMElement;
use PHPUnit\Framework\Assert;
use Vehikl\LaravelTwilioProgrammableVoiceTestRig\CallStatus;

class Reject extends Element
{
    public function callStatus(CallStatus $previous): CallStatus
    {
        Assert::assertNotEquals(CallStatus::in_progress, $previous, 'Cannot reject a call that has been interacted with');

        return $this->attr('reason') === 'busy'
            ? CallStatus::busy
            : CallStatus::no_answer;
    }

    public function nextElement(): ?DOMElement
    {
        return null;
    }
}

