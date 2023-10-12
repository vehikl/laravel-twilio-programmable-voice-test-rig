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
            ->queueInput(recordingUrl: 'file.mp3', recordingDuration: 1)
            ->ring(from: '15554443322', to: '12223334455')
//            ->assertTwimlEquals('...full twiml...')
//            ->assertTwimlContains('<Say>....</Say>')
//            ->assertSaid('Thing')
            ->assert(function (Assert $assert) {
                $assert->twiml('<Say>Record your name</Say><Record action="%s"/><Redirect method="POST">%s</Redirect>', route('multiple-step.thanks'), route('multiple-step.emptyRecordingRetry'));
            })
            ->assertRedirectedTo(route('multiple-step.thanks'))
            ->assert(function (Assert $assert) {
                $assert->twiml('<Say>%s</Say><Hangup/>', 'Thank-you for recording your name');
            })
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
            ->assertTwimlEquals(
                <<<TWIML
                <Say>Record your name</Say>
                <Record action="%s"/>
                <Redirect method="POST">%s</Redirect>
TWIML,
                route('multiple-step.thanks'),
                route('multiple-step.emptyRecordingRetry')
            )
            ->assertRedirectedTo(route('multiple-step.emptyRecordingRetry'))
            ->assertSaid('Oops, we couldn\'t hear you, try again')
            ->assertRedirectedTo(route('multiple-step.record'))
            ->assertCallStatus(CallStatus::in_progress);
    }
}
