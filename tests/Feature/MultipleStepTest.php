<?php

namespace Tests\Feature;

use Tests\TestCase;
use Vehikl\LaravelTwilioProgrammableVoiceTestRig\CallStatus;
use Vehikl\LaravelTwilioProgrammableVoiceTestRig\ProgrammableVoiceRig;
use Vehikl\LaravelTwilioProgrammableVoiceTestRig\TwimlApp;
use Vehikl\LaravelTwilioProgrammableVoiceTestRig\TwimlAppConfiguration;

class MultipleStepTest extends TestCase
{
    /** @test */
    public function itFollowsRecordActionWhenRecordingPresent(): void
    {
        (new ProgrammableVoiceRig(
            $this->app,
            new TwimlApp(
                voice: new TwimlAppConfiguration(
                    requestUrl: route('multiple-step.record'),
                ),
            ),
        ))
            ->ring(from: '15554443322', to: '12223334455')
            ->assertTwimlOrder(['Say', 'Record', 'Redirect'])
            ->assertSaid('Record your name')
            ->assertTwimlContains('<Record action="%s"/>', route('multiple-step.thanks'))
            ->assertTwimlContains('<Redirect method="POST">%s</Redirect>', route('multiple-step.emptyRecordingRetry'))
            ->record(recordingUrl: 'file.mp3', recordingDuration: 5)
            ->assertTwilioRedirectedTo(route('multiple-step.thanks'), byTwimlTag: 'Record')
            ->assertSaid('Thank-you for recording your name')
            ->assertTwimlContains('<Hangup/>')
            ->assertCallEnded();
    }

    /** @test */
    public function itSkipsRecordingTwimlWhenNoRecordingGiven(): void
    {
        (new ProgrammableVoiceRig(
            $this->app,
            new TwimlApp(
                voice: new TwimlAppConfiguration(
                    requestUrl: route('multiple-step.record'),
                ),
            ),
        ))
            ->ring(from: '15554443322', to: '12223334455')
            ->assertSaid('Record your name')
            ->assertTwimlContains('<Record action="%s"/>', route('multiple-step.thanks'))
            ->assertTwimlContains('<Redirect method="POST">%s</Redirect>', route('multiple-step.emptyRecordingRetry'))
            ->assertTwilioRedirectedTo(route('multiple-step.emptyRecordingRetry'))
            ->assertPlayed('sad-trombone.mp3')
            ->assertPaused(2)
            ->assertSaid('Oops, we couldn\'t hear you, try again')
            ->assertTwilioRedirectedTo(route('multiple-step.record'))
            ->assertCallStatus(CallStatus::in_progress);
    }
}
