<?php

namespace Tests\Feature;

use Tests\TestCase;
use Vehikl\LaravelTwilioProgrammableVoiceTestRig\ProgrammableVoiceRig;

class SingleStepTest extends TestCase
{
    /** @test */
    public function itHandlesACallWithASingleStep(): void
    {
        (new ProgrammableVoiceRig($this->app))
            ->phoneCall(from: '15554443322', to: '12223334455', endpoint: route('single-step'))
            ->assertSay('Saying something here')
            ->assertCallEnded();
    }
}
