<?php

namespace Tests\Unit\Events;

use DOMDocument;
use DOMElement;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Vehikl\LaravelTwilioProgrammableVoiceTestRig\CallStatus;
use Vehikl\LaravelTwilioProgrammableVoiceTestRig\Events\DialEvent;
use Vehikl\LaravelTwilioProgrammableVoiceTestRig\Handlers\Element;
use Vehikl\LaravelTwilioProgrammableVoiceTestRig\ProgrammableVoiceRig;

class DialEventTest extends TestCase
{
    private MockInterface $rig;
    private DOMDocument $dom;
    private DOMElement $dial;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rig = $this->mock(ProgrammableVoiceRig::class);
        $this->dom = new DOMDocument();
        $this->dial = $this->dom->createElement('Dial');
    }
    /**
     * @param array<string,string> $attributes
     */
    protected function setDialAttributes(array $attributes): void
    {
        foreach ($attributes as $key => $value) {
            $this->dial->setAttribute($key, $value);
        }
    }
    /**
     * @param array<string,string> $dialAttributes
     */
    protected function makeEvent(array $dialAttributes): DialEvent
    {
        $this->setDialAttributes($dialAttributes);
        $element = Element::fromElement($this->rig, $this->dial);
        return new DialEvent($this->rig, $element);
    }


    /**
     * @test
     */
    public function itAnswersTheCallAndRecordsIt(): void
    {
        $action = '/test';
        $callSid = 'CA' . fake()->uuid;
        $parentCallSid = 'CA' . fake()->uuid;
        $accountSid = 'AC' . fake()->uuid;
        $bridged = fake()->randomElement([true, false]);
        $recordingUrl = fake()->url;

        $event = $this->makeEvent([
            'record' => 'true',
            'action' => $action,
            'method' => 'POST',
        ]);

        $this->rig->shouldReceive('parameter')->with('CallSid', Mockery::any())->andReturn($parentCallSid)->once();

        $this->rig->shouldReceive('navigate')
            ->with(
                $action,
                'POST',
                [
                    'DialCallStatus' => CallStatus::completed->value,
                    'DialCallSid' => $callSid,
                    'DialBridged' => $bridged,
                    'RecordingUrl' => $recordingUrl,
                ]
            )
            ->once();

        $event->withAnswer($callSid, bridged: $bridged, recordingUrl: $recordingUrl);
    }

    /**
     * @test
     */
    public function itSendsRecordingStatusWebhooks(): void
    {
        $action = '/test';
        $callSid = fake()->uuid;
        $parentCallSid = fake()->uuid;
        $accountSid = fake()->uuid;
        $bridged = fake()->randomElement([true, false]);
        $recordStatusCallback = '/status';

        $event = $this->makeEvent([
            'record' => 'true',
            'action' => $action,
            'method' => 'POST',
            'recordingStatusCallback' => $recordStatusCallback,
            'recordingStatusCallbackEvent' => 'absent',
        ]);

        $this->rig->shouldReceive('navigate')->once();
        $this->rig->shouldReceive('parameter')->with('CallSid', Mockery::any())->andReturn($parentCallSid)->once();
        $this->rig->shouldReceive('parameter')->with('AccountSid')->andReturn($accountSid);
        $this->rig->shouldReceive('hitStatusCallback')
            ->with(
                'POST',
                $recordStatusCallback,
                Mockery::subset([
                    'AccountSid' => $accountSid,
                    'CallSid' => $parentCallSid,
                    'RecordingSource' => 'DialVerb',
                    'RecordingStatus' => 'absent',
                ]),
            )
            ->once();

        $event->withAnswer($callSid, bridged: $bridged, recordingUrl: null);
    }

    /**
     * @test
     */
    public function itDoesNotCallStatusCallbackWhenNotPresent(): void
    {
        $action = '/test';
        $callSid = fake()->uuid;
        $parentCallSid = fake()->uuid;
        $accountSid = fake()->uuid;
        $bridged = fake()->randomElement([true, false]);
        $recordStatusCallback = '/status';

        $event = $this->makeEvent([
            'record' => 'true',
            'action' => $action,
            'method' => 'POST',
            'recordingStatusCallbackEvent' => 'absent complete',
        ]);

        $this->rig->shouldReceive('navigate')->once();
        $this->rig->shouldReceive('parameter')->with('CallSid', Mockery::any())->andReturn($parentCallSid)->once();
        $this->rig->shouldReceive('parameter')->with('AccountSid')->andReturn($accountSid);
        $this->rig->shouldReceive('hitStatusCallback')->never();

        $event->withAnswer($callSid, bridged: $bridged, recordingUrl: 'recordings.com/recording');
    }

    /**
     * @test
     */
    public function itHandlesABusyCall(): void
    {
        $action = '/test';
        $callSid = fake()->uuid;
        $parentCallSid = fake()->uuid;
        $accountSid = fake()->uuid;
        $bridged = fake()->randomElement([true, false]);
        $recordStatusCallback = '/status';

        $event = $this->makeEvent([
            'record' => 'true',
            'action' => $action,
            'method' => 'POST',
            'recordingStatusCallbackEvent' => 'absent',
        ]);

        $this->rig->shouldReceive('navigate')->once();
        $this->rig->shouldReceive('parameter')->with('CallSid', Mockery::any())->andReturn($parentCallSid)->once();
        $this->rig->shouldReceive('parameter')->with('AccountSid')->andReturn($accountSid);
        $this->rig->shouldReceive('hitStatusCallback')->never();

        $event->withBusy();
        
    }
}
