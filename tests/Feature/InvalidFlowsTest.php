<?php

namespace Tests\Feature;

use Tests\TestCase;
use Vehikl\LaravelTwilioProgrammableVoiceTestRig\ProgrammableVoiceRig;

class InvalidFlowsTest extends TestCase
{
    /** @test */
    public function itFailsOnBadXMLResponse(): void
    {
        (new ProgrammableVoiceRig($this->app))
            ->phoneCall(from: '15554443322', to: '12223334455', endpoint: route('invalid-flows.json'))
            ->assertCallEnded();
    }
}
