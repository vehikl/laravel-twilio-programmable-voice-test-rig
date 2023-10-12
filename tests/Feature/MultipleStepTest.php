<?php

namespace Tests\Feature;

use Tests\TestCase;
use Vehikl\LaravelTwilioProgrammableVoiceTestRig\Assert;
use Vehikl\LaravelTwilioProgrammableVoiceTestRig\PhoneNumber;
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
            ->assert(function (Assert $assert) {
                $assert
                    ->twiml('<Say>Record your name</Say><Record action="%s"/><Redirect method="POST">%s</Redirect>', route('multiple-step.thanks'), route('multiple-step.emptyRecordingRetry'))
                    ->endpoint(route('multiple-step.record'));
            })
            ->assertRedirectedTo(route('multiple-step.thanks'))
            ->assert(function (Assert $assert) {
                $assert
                    ->endpoint(route('multiple-step.thanks'))
                    ->twiml('<Say>%s</Say><Hangup/>', 'Thank-you for recording your name');
            })
            ->assertRedirectedTo('foo')
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
            ->assert(function (Assert $assert) {
                $assert
                    ->twiml('<Say>Record your name</Say><Record action="%s"/><Redirect method="POST">%s</Redirect>', route('multiple-step.thanks'), route('multiple-step.emptyRecordingRetry'))
                    ->endpoint(route('multiple-step.record'))
                    ->callStatus('in-progress');
            })
            ->followTwiml()->assert(function (Assert $assert) {
                $assert
                    ->endpoint(route('multiple-step.emptyRecordingRetry'))
                    ->twiml('<Say>%s</Say><Redirect method="POST">%s</Redirect>', 'Oops, we couldn\'t hear you, try again', route('multiple-step.record'))
                    ->callStatus('in-progress');
            });
    }
}
