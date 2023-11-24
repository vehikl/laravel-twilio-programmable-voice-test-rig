<?php

namespace Vehikl\LaravelTwilioProgrammableVoiceTestRig\Events;

use Vehikl\LaravelTwilioProgrammableVoiceTestRig\CallStatus;
use Vehikl\LaravelTwilioProgrammableVoiceTestRig\ProgrammableVoiceRig;

class DialEvent extends Event
{
    public function withAnswer(?string $callSid = null, int $duration = 60, bool $bridged = true, ?string $recordingUrl = null): ProgrammableVoiceRig
    {
        $recordingAttribute = ($this->element->attr('record', 'do-not-record') != 'do-not-record') && $recordingUrl
            ? ['RecordingUrl' => $recordingUrl]
            : [];

        return $this->rig->navigate(
            $this->element->attr('action', $this->rig->requestedUri),
            $this->element->attr('method', 'POST'),
            [
                'DialCallStatus' => CallStatus::completed->value,
                'DialCallSid' => $callSid ?? sprintf('CA%s', fake()->uuid),
                'DialBridged' => $bridged,
                ...$recordingAttribute,
            ],
        );
    }

    public function withBusy(): ProgrammableVoiceRig
    {
        return $this->rig;
    }
}
