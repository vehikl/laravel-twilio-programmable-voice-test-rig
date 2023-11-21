<?php

namespace Tests\Feature;

use Tests\TestCase;
use Vehikl\LaravelTwilioProgrammableVoiceTestRig\CallStatus;
use Vehikl\LaravelTwilioProgrammableVoiceTestRig\ProgrammableVoiceRig;

class AnonymousDialingTest extends TestCase
{

    /** @test */
    public function itCanGatherDigitsAndMakeAPhoneCall(): void
    {
        (new ProgrammableVoiceRig($this->app))
            ->phoneCall(from: '15554443322', to: '12223334455', endpoint: route('anonymous-dialing.gather'))
            ->assertGather([
                'action' => route('anonymous-dialing.dial'),
                'input' => 'dtmf',
                'finishOnKey' => '#',
                'numDigits' => 12,
                'actionOnEmptyResult' => false,
            ])
            ->assertChildren(function (ProgrammableVoiceRig $context) {
                return $context->assertSay('Dial North-American Number, then press pound');
            })
            ->withDigits('5554443322#')
            ->assertEndpoint(route('anonymous-dialing.dial'))
            ->assertSay('Dialing 5 5 5 4 4 4 3 3 2 2, please wait')
            ->assertPause(1)
            ->dial(CallStatus::completed, duration: 30)
            ->assertDial('5554443322', ['action' => route('anonymous-dialing.completed'), 'method' => 'POST'])
            ->withAnswer()
            ->assertSay('Phone call completed')
            ->assertHangup()
            ->assertCallEnded();
    }

    /** @test */
    public function itFailsIfProvidedNoGather(): void
    {
        (new ProgrammableVoiceRig($this->app))
            ->phoneCall(from: '15554443322', to: '12223334455', endpoint: route('anonymous-dialing.gather'))
            ->assertGather([
                'action' => route('anonymous-dialing.dial'),
                'input' => 'dtmf',
                'finishOnKey' => '#',
                'numDigits' => 12,
                'actionOnEmptyResult' => false,
            ], true)
            ->assertChildren(function (ProgrammableVoiceRig $context) {
                return $context->assertSay('Dial North-American Number, then press pound');
            })
            ->withNothing()
            ->assertSay('Please try again later')
            ->assertHangup()
            ->assertCallEnded();
    }
}
