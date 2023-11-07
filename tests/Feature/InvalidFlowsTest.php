<?php

namespace Tests\Feature;

use Tests\TestCase;
use Vehikl\LaravelTwilioProgrammableVoiceTestRig\ProgrammableVoiceRig;
use Vehikl\LaravelTwilioProgrammableVoiceTestRig\TwimlApp;
use Vehikl\LaravelTwilioProgrammableVoiceTestRig\TwimlAppConfiguration;

class InvalidFlowsTest extends TestCase
{
    /** @test */
    public function itFailsOnBadXMLResponse(): void
    {
        (new ProgrammableVoiceRig(
            $this->app,
            new TwimlApp(
                voice: new TwimlAppConfiguration(
                    requestUrl: route('invalid-flows.json'),
                ),
            ),
        ))
            ->ring(from: '15554443322', to: '12223334455')
            ->assertCallEnded();
    }
}
