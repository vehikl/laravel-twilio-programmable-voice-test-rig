<?php

namespace Tests\Unit\Events;

use Tests\TestCase;
use Vehikl\LaravelTwilioProgrammableVoiceTestRig\ProgrammableVoiceRig;

class GatherEventTest extends TestCase
{
    /**
     * @test
     */
    public function itExecutesNextTwimlInstructionIfActionOnEmptyResultIsFalseAndResultsIsSilence(): void
    {

        (new ProgrammableVoiceRig($this->app))
            ->fromTwiml('<Response><Gather action="/foo/bar" actionOnEmptyResult="false"/><Hangup/></Response>')
            ->assertGather(['action' => '/foo/bar', 'actionOnEmptyResult' => false])
            ->withSilence()
            ->assertHangup()
            ->assertCallEnded();
    }

    /**
     * @test
     */
    public function itTreatsSilenceAsEmptyDigits(): void
    {
        $route = route('single-step.reject');
        (new ProgrammableVoiceRig($this->app))
            ->fromTwiml(sprintf('<Response><Gather action="%s" actionOnEmptyResult="true"/><Hangup/></Response>', $route))
            ->assertGather(['action' => $route, 'actionOnEmptyResult' => true])
            ->withSilence()
            ->assertEndpoint($route);
    }
}
