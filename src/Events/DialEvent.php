<?php

namespace Vehikl\LaravelTwilioProgrammableVoiceTestRig\Events;

use Carbon\Carbon;
use Vehikl\LaravelTwilioProgrammableVoiceTestRig\CallStatus;
use Vehikl\LaravelTwilioProgrammableVoiceTestRig\ProgrammableVoiceRig;

class DialEvent extends Event
{
    public function withAnswer(?string $callSid = null, int $duration = 60, bool $bridged = true, ?string $recordingUrl = null): ProgrammableVoiceRig
    {
        $recordingAttribute = ($this->element->attr('record', 'do-not-record') != 'do-not-record') && $recordingUrl
            ? ['RecordingUrl' => $recordingUrl]
            : [];

        $recordingEvents = explode(' ', ($this->element->attr('recordingStatusCallbackEvent', 'completed')));
        if (!$recordingUrl && in_array('absent', $recordingEvents)) {
            $this->rig->hitStatusCallback(
                $this->element->attr('recordingStatusCallbackMethod', 'POST'),
                $this->element->attr('recordingStatusCallback'),
                [
                    'CallSid' => $callSid,
                    'ParentCallSid' => $this->rig->parameter('CallSid'),
                    'RecordingStatus' => 'absent'
                ],
            );
        } else {
            foreach (['in-progress', 'complete'] as $status) {
                if (!in_array($status, $recordingEvents)) {
                    continue;
                }

                $this->rig->hitStatusCallback(
                    $this->element->attr('recordingStatusCallbackMethod', 'POST'),
                    $this->element->attr('recordingStatusCallback'),
                    [
                        'AccountSid' => $this->rig->parameter('AccountSid'),
                        'CallSid' => $callSid,
                        'RecordingStatus' => $status,
                        'RecordingChannels' => 2,
                        'RecordingStartTime' => match($status) {
                            'complete' => Carbon::now()->subSeconds($duration),
                            default => Carbon::now(),
                        },
                        'RecordingSource' => 'DialVerb',
                    ],
                );
            }
        }

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
        $callSid = sprintf('CA%s', fake()->uuid);
        $recordingEvents = explode(' ', ($this->element->attr('recordingStatusCallbackEvent', 'completed')));
        if (in_array('absent', $recordingEvents)) {
            $this->rig->hitStatusCallback(
                $this->element->attr('recordingStatusCallbackMethod', 'POST'),
                $this->element->attr('recordingStatusCallback'),
                [
                    'CallSid' => $callSid,
                    'ParentCallSid' => $this->rig->parameter('CallSid'),
                    'RecordingStatus' => 'absent'
                ],
            );
        }

        return $this->rig->navigate(
            $this->element->attr('action', $this->rig->requestedUri),
            $this->element->attr('method', 'POST'),
            [
                'DialCallStatus' => CallStatus::busy->value,
                'DialCallSid' => $callSid,
                'DialBridged' => false,
            ],
        );
    }
}
