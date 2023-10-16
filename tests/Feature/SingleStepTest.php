<?php

namespace Tests\Feature;

use Tests\TestCase;
use Vehikl\LaravelTwilioProgrammableVoiceTestRig\Assert;
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
            ->ring(from: '15554443322', to: '12223334455')
            ->assertSaid('Saying something here')
            ->assertCallEnded();
    }
}
