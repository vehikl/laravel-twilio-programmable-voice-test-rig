<?php

namespace Tests\Feature;

use Tests\TestCase;
use Vehikl\LaravelTwilioProgrammableVoiceTestRig\Assert;
use Vehikl\LaravelTwilioProgrammableVoiceTestRig\CallStatus;
use Vehikl\LaravelTwilioProgrammableVoiceTestRig\ProgrammableVoiceRig;
use Vehikl\LaravelTwilioProgrammableVoiceTestRig\TwimlApp;
use Vehikl\LaravelTwilioProgrammableVoiceTestRig\TwimlAppConfiguration;

class MultipleStepTest extends TestCase
{
    /** @test */
    public function itHandlesAMultiStepFlow(): void
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
            ->record(recordingUrl: 'file.mp3')
            ->assertRedirectedTo(route('multiple-step.thanks'))
            ->assertSaid('Thank-you for recording your name')
            ->assertTwimlContains('<Hangup/>')
            ->assertCallEnded();
    }

    /** @test */
    public function itHandlesNoQueuedInput(): void
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
            ->assertRedirectedTo(route('multiple-step.emptyRecordingRetry'))
            ->assertSaid('Oops, we couldn\'t hear you, try again')
            ->assertRedirectedTo(route('multiple-step.record'))
            ->assertCallStatus(CallStatus::in_progress);
    }
}
