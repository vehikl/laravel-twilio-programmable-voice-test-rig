<?php

namespace Tests\Unit\Events;

use DOMDocument;
use DOMElement;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Vehikl\LaravelTwilioProgrammableVoiceTestRig\CallStatus;
use Vehikl\LaravelTwilioProgrammableVoiceTestRig\Events\DialEvent;
use Vehikl\LaravelTwilioProgrammableVoiceTestRig\Events\RecordEvent;
use Vehikl\LaravelTwilioProgrammableVoiceTestRig\Handlers\Element;
use Vehikl\LaravelTwilioProgrammableVoiceTestRig\ProgrammableVoiceRig;

class RecordEventTest extends TestCase
{
    private MockInterface $rig;
    private DOMDocument $dom;
    private DOMElement $record;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rig = $this->mock(ProgrammableVoiceRig::class);
        $this->dom = new DOMDocument();
        $this->record = $this->dom->createElement('Record');
    }
    /**
     * @param array<string,string> $attributes
     */
    protected function setRecordAttributes(array $attributes): void
    {
        foreach ($attributes as $key => $value) {
            $this->record->setAttribute($key, $value);
        }
    }
    /**
     * @param array<string,string> $dialAttributes
     */
    protected function makeEvent(array $dialAttributes): RecordEvent
    {
        $this->setRecordAttributes($dialAttributes);
        $element = Element::fromElement($this->rig, $this->record);
        return new RecordEvent($this->rig, $element);
    }


    /**
     * @test
     */
    public function itEndsAudioWithADigit(): void
    {
        $action = '/test';
        $callSid = 'CA' . fake()->uuid;
        $parentCallSid = 'CA' . fake()->uuid;
        $accountSid = 'AC' . fake()->uuid;
        $recordingUrl = fake()->url;
        $duration = 5;
        $digits = (string)fake()->numerify('###');

        $event = $this->makeEvent([
            'action' => $action,
            'method' => 'POST',
        ]);

        $this->rig->shouldReceive('navigate')
            ->with(
                $action,
                'POST',
                [
                    'RecordingUrl' => $recordingUrl,
                    'RecordingDuration' => $duration,
                    'Digits' => $digits,
                ]
            )
            ->once();

        $event->withAudio($recordingUrl, $duration, $digits);
    }
}
