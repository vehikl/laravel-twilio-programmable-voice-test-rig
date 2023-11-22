<?php

namespace Tests\Feature;

use Tests\TestCase;
use Vehikl\LaravelTwilioProgrammableVoiceTestRig\CallStatus;
use Vehikl\LaravelTwilioProgrammableVoiceTestRig\ProgrammableVoiceRig;
use Vehikl\LaravelTwilioProgrammableVoiceTestRig\Rejection;

class SingleStepTest extends TestCase
{
    /** @test */
    public function itHandlesACallWithASingleStep(): void
    {
        (new ProgrammableVoiceRig($this->app))
            ->phoneCall(from: '15554443322', to: '12223334455', endpoint: route('single-step.sayAndHangup'))
            ->assertSay('Saying something here')
            ->assertCallEnded();
    }

    /** @test */
    public function itRejectsTheCallWithOutOfService(): void
    {
        (new ProgrammableVoiceRig($this->app))
            ->to('15554443322')
            ->from('18887776655')
            ->phoneCall(endpoint: route('single-step.reject', ['reason' => Rejection::rejected->value]))
            ->assertRejected(Rejection::rejected)
            ->assertCallEnded();
    }

    /** @test */
    public function itRejectsTheCallWithBusy(): void
    {
        (new ProgrammableVoiceRig($this->app))
            ->to('15554443322')
            ->from('18887776655')
            ->phoneCall(endpoint: route('single-step.reject', ['reason' => Rejection::busy->value]))
            ->assertRejected(Rejection::busy)
            ->assertCallEnded();
    }

    /** @test */
    public function itUsesPauseToAllowMoreRinging(): void
    {
        (new ProgrammableVoiceRig($this->app))
            ->to('15554443322')
            ->from('18887776655')
            ->phoneCall(endpoint: route('single-step.ring'))
            ->assertCallStatus(CallStatus::ringing)
            ->assertPause(5)
            ->assertCallStatus(CallStatus::ringing)
            ->assertHangup()
            ->assertCallStatus(CallStatus::completed)
            ->assertCallEnded();
    }

    public function itStopsExecutionOfTwimlResponseAfterRedirect(): void
    {
        (new ProgrammableVoiceRig($this->app))
            ->to('15554443322')
            ->from('18887776655')
            ->phoneCall(endpoint: route('single-step.redirectBlocksOtherInstructions'))
            ->assertRedirect(route('single-step.sayAndHangup'))
            ->assertEndpoint(route('single-step.sayAndHangup'))
            ->assertSay('Say something here')
            ->assertCallEnded();
        
    }
}
