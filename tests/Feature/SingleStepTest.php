<?php

namespace Tests\Feature;

use Tests\TestCase;
use Vehikl\LaravelTwilioProgrammableVoiceTestRig\Assert;
use Vehikl\LaravelTwilioProgrammableVoiceTestRig\PhoneNumber;
use Vehikl\LaravelTwilioProgrammableVoiceTestRig\ProgrammableVoiceRig;
use Vehikl\LaravelTwilioProgrammableVoiceTestRig\TwimlApp;
use Vehikl\LaravelTwilioProgrammableVoiceTestRig\TwimlAppConfiguration;

class SingleStepTest extends TestCase
{
    /** @test */
    public function itHandlesACallWithASingleStep(): void
    {
        (new ProgrammableVoiceRig(
            $this->app,
            new TwimlApp(
                voice: new TwimlAppConfiguration(
                    requestUrl: route('single-step'),
                ),
            ),
        ))
            ->from(new PhoneNumber('15554443322'))
            ->to(new PhoneNumber('12223334455'))
            ->call()
            ->followTwiml()
            ->assert(function (Assert $assert) {
                $assert
                    ->endpoint(route('single-step'))
                    ->twiml('<Say>%s</Say>', 'Saying something here')
                    ->callStatus('completed');
            });
    }
}