<?php

namespace Tests\Feature;

use Tests\TestCase;
use Vehikl\LaravelTwilioProgrammableVoiceTestRig\AssertContext;
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
            // ->assertTwimlOrder([
            //     fn (AssertContext $ctx) => $ctx->assertSay('Record your name'),
            //     fn (AssertContext $ctx) => $ctx->assertRecord(),
            //     fn (AssertContext $ctx) => $ctx->assertRedirect(route('multiple-step.emptyRecordingRetry')),
            // ])
            ->assertTwimlOrder(['Say', 'Record', 'Redirect'])
            ->assertSay('Record your name')
            ->assertRecord(['action' => route('multiple-step.thanks')])
            // ->assertTwimlContains('<Record action="%s"/>', route('multiple-step.thanks'))
            ->assertTwimlContains('<Redirect method="POST">%s</Redirect>', route('multiple-step.emptyRecordingRetry'))
            ->record(recordingUrl: 'file.mp3', recordingDuration: 5)
            ->assertTwilioHit(route('multiple-step.thanks'), byTwimlTag: 'Record')
            ->assertSay('Thank-you for recording your name')
            ->assertHangup()
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
            ->assertSay('Record your name')
            ->assertRecord(['action' => route('multiple-step.thanks')])
            ->assertTwimlContains('<Redirect method="POST">%s</Redirect>', route('multiple-step.emptyRecordingRetry'))
            ->assertTwilioHit(route('multiple-step.emptyRecordingRetry'))
            ->assertPlay('sad-trombone.mp3')
            ->assertPause(2)
            ->assertSay('Oops, we couldn\'t hear you, try again')
            ->assertTwilioHit(route('multiple-step.record'))
            ->assertCallStatus(CallStatus::in_progress);
    }
}
