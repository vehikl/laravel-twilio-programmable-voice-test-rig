<?php

namespace Tests\Feature;

use Tests\TestCase;
use Vehikl\LaravelTwilioProgrammableVoiceTestRig\PhoneNumber;
use Vehikl\LaravelTwilioProgrammableVoiceTestRig\ProgrammableVoiceRig;

class FirstTest extends TestCase
{
    /** @test */
    public function itDoesStuff(): void
    {
        $this->assertEquals(1, 2);
        return;
    }
    // ...

    /** @test */
    public function itDoesABasicFlow(): void
    {
        (new ProgrammableVoiceRig($this->resolveApplication()))
            ->from(new PhoneNumber('15554443322'))
            ->to(new PhoneNumber('12223334455'))
            ->endpoint(route('stepOne'))
            ->assertNextEndpoint(route('stepTwo'))
            ->assertTwiml('<Say>%s</Say>', 'Saying something here');
    }
}