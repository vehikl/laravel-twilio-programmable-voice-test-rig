<?php

namespace Vehikl\LaravelTwilioProgrammableVoiceTestRig\Events;

use Carbon\Carbon;
use Vehikl\LaravelTwilioProgrammableVoiceTestRig\CallStatus;
use Vehikl\LaravelTwilioProgrammableVoiceTestRig\ProgrammableVoiceRig;

class DialEvent extends Event
{
    const STATUS_CALLBACK_STRUCTURE = [
        'AccountSid' => '',
        'CallSid' => '',
        'RecordingChannels' => null,
        'RecordingDuration' => null,
        'RecordingSid' => null,
        'RecordingSource' => 'DialVerb',
        'RecordingStartTime' => null,
        'RecordingStatus' => '',
        'RecordingUrl' => null,
    ];

    /**
     * @param array<string,mixed> $statusAttributes
     */
    protected function sendStatusCallback(string $status, array $statusAttributes): void
    {
        $uri = $this->element->attr('recordingStatusCallback');
        if (!$uri) {
            return;
        }

        $attributes = collect([
            ...self::STATUS_CALLBACK_STRUCTURE,
            ...$statusAttributes,
            'RecordingStatus' => $status,
            'AccountSid' => $this->rig->parameter('AccountSid'),
        ])
            ->filter(fn ($value) => $value !== null)
            ->toArray();

        $this->rig->hitStatusCallback(
            $this->element->attr('recordingStatusCallbackMethod', 'POST'),
            $uri,
            $attributes,
        );
    }

    protected function sendRecordingStatusEvents(string $callSid, ?string $recordingUrl, ?string $recordingSid, int $duration): void
    {
        $recordingEvents = explode(' ', $this->element->attr('recordingStatusCallbackEvent', 'completed'));
        if (!$recordingUrl && in_array('absent', $recordingEvents)) {
            $this->sendStatusCallback('absent', ['CallSid' => $callSid]);
        }

        if ($recordingUrl) {
            foreach (['in-progress', 'complete'] as $status) {
                if (!in_array($status, $recordingEvents)) {
                    continue;
                }

                $this->sendStatusCallback($status, [
                    'CallSid' => $callSid,
                    'RecordingChannels' => 2,
                    'RecordingStartTime' => match($status) {
                        'complete' => Carbon::now()->subSeconds($duration),
                        default => Carbon::now(),
                    },
                    'RecordingUrl' => $recordingUrl,
                    'RecordingSid' => $recordingSid
                ]);
            }
        }
    }

    public function withAnswer(?string $callSid = null, int $duration = 60, bool $bridged = true, ?string $recordingUrl = null, ?string $recordingSid = null): ProgrammableVoiceRig
    {
        $shouldRecord = !in_array($this->element->attr('record', 'do-not-record'), ['false', 'do-not-record']);

        $recordingAttribute = $shouldRecord && $recordingUrl
            ? ['RecordingUrl' => $recordingUrl]
            : [];

        if ($shouldRecord) {
            $this->sendRecordingStatusEvents(
                $this->rig->parameter('CallSid', $callSid),
                $recordingUrl,
                $recordingSid ?? ('RE' . fake()->uuid),
                $duration,
            );
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
            $this->sendStatusCallback('absent', [
                'CallSid' => $this->rig->parameter('CallSid', $callSid),
                'RecordingStatus' => 'absent'
            ]);
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
