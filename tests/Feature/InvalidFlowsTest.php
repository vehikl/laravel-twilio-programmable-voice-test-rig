<?php

namespace Tests\Feature;

use Tests\TestCase;
use Vehikl\LaravelTwilioProgrammableVoiceTestRig\ProgrammableVoiceRig;

class InvalidFlowsTest extends TestCase
{
    /** @test */
    public function itFailsOnJsonResponse(): void
    {
        (new ProgrammableVoiceRig($this->app))
            ->phoneCall(from: '15554443322', to: '12223334455', endpoint: route('invalid-flows.json'))
            ->assertInvalidResponse()
            ->assertCallEnded();
    }

    /** @test */
    public function itFailsOnStringResponse(): void
    {
        (new ProgrammableVoiceRig($this->app))
            ->phoneCall(from: '15554443322', to: '12223334455', endpoint: route('invalid-flows.string'))
            ->assertInvalidResponse()
            ->assertCallEnded();
    }
}
