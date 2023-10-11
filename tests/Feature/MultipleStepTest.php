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
            ->from(new PhoneNumber('15554443322'))
            ->to(new PhoneNumber('12223334455'))
            ->queueInput(recordingUrl: 'file.mp3', recordingDuration: 1)
            ->call()
            ->assert(function (Assert $assert) {
                $assert
                    // ->endpoint(route('multiple-step.record'))
                    ->twiml('<Record action="%s"/><Redirect method="POST">%s</Redirect>', route('multiple-step.thanks'), route('multiple-step.emptyRecordingRetry'))
                    ->callStatus('in-progress');
            });
            // ->followTwiml()->assert(function (Assert $assert) {
            //     $assert
            //         ->endpoint(route('multiple-step.thanks'))
            //         ->twiml('<Say>%s</Say><Hangup/>', 'Thank-you for recording your name')
            //         ->callStatus('completed');
            // });
    }
}
