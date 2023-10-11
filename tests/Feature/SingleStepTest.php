<?php

namespace Tests\Feature;

use Tests\TestCase;
use Vehikl\LaravelTwilioProgrammableVoiceTestRig\PhoneNumber;
use Vehikl\LaravelTwilioProgrammableVoiceTestRig\ProgrammableVoiceRig;

class SingleStepTest extends TestCase
{
    /** @test */
    public function itDoesABasicFlow(): void
    {
        (new ProgrammableVoiceRig($this->app))
            ->from(new PhoneNumber('15554443322'))
            ->to(new PhoneNumber('12223334455'))
            ->endpoint(route('single-step'))
            ->assertTwiml('<Say>%s</Say>', 'Saying something here')
            ->assertCallEnded();
    }
}
