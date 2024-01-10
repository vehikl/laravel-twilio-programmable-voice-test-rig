<?php

namespace Tests\Unit;

use Tests\TestCase;
use Vehikl\LaravelTwilioProgrammableVoiceTestRig\ProgrammableVoiceRig;

class ProgrammableVoiceRigTest extends TestCase
{
    /**
     * @test
     */
    public function itCanSkipOverNonActionableTwimlTags(): void
    {
        (new ProgrammableVoiceRig($this->app))
            ->from(fake()->numerify('12#########'))
            ->to(fake()->numerify('12#########'))
            ->fromTwiml('<Response><Say>hi</Say><Play>some/url/here</Play><Gather/><Hangup/></Response>')
            ->skipUntilActionableTag()
            ->assertGather();
    }
}
