<?php

namespace Tests\Feature;

use Tests\TestCase;
use Vehikl\LaravelTwilioProgrammableVoiceTestRig\CallStatus;
use Vehikl\LaravelTwilioProgrammableVoiceTestRig\ProgrammableVoiceRig;

class MultipleStepTest extends TestCase
{
    /** @test */
    public function itFollowsRecordActionWhenRecordingPresent(): void
    {
        (new ProgrammableVoiceRig($this->app))
            ->phoneCall(from: '15554443322', to: '12223334455', endpoint: route('multiple-step.record'))
            ->assertSay('Record your name')
            ->assertRecord(['action' => route('multiple-step.thanks')])
            ->withAudio(recordingUrl: 'file.mp3', recordingDuration: 5)
            ->assertEndpoint(route('multiple-step.thanks'), 'POST')
            ->assertSay('Thank-you for recording your name')
            ->assertHangup()
            ->assertCallEnded();
    }

    /** @test */
    public function itSkipsRecordingTwimlWhenNoRecordingGiven(): void
    {
        (new ProgrammableVoiceRig($this->app))
            ->phoneCall(from: '15554443322', to: '12223334455', endpoint: route('multiple-step.record'))
            ->assertSay('Record your name')
            ->assertRecord(['action' => route('multiple-step.thanks')])
            ->withSilence()
            ->assertRedirect(route('multiple-step.emptyRecordingRetry'), 'POST')
            ->assertPlay('sad-trombone.mp3')
            ->assertPause(2)
            ->assertSay('Oops, we couldn\'t hear you, try again')
            ->assertCallStatus(CallStatus::in_progress);
    }
}
