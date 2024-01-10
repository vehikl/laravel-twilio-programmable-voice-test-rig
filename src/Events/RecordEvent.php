<?php

namespace Vehikl\LaravelTwilioProgrammableVoiceTestRig\Events;

use Vehikl\LaravelTwilioProgrammableVoiceTestRig\ProgrammableVoiceRig;

class RecordEvent extends Event
{
    public function withAudio(string $recordingUrl, int $recordingDuration, ?string $digits = null): ProgrammableVoiceRig
    {
        $digitAttributes = $digits
            ? ['Digits' => $digits]
            : [];

        return $this->rig->navigate(
            $this->element->attr('action'),
            $this->element->attr('method', 'POST'),
            [
                'RecordingUrl' => $recordingUrl,
                'RecordingDuration' => $recordingDuration,
                ...$digitAttributes,
            ]
        );
    }

    public function withSilence(): ProgrammableVoiceRig
    {
        return $this->rig;
    }
}
