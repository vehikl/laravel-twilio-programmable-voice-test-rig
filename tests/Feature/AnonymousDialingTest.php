<?php

namespace Tests\Feature;

use Tests\TestCase;
use Vehikl\LaravelTwilioProgrammableVoiceTestRig\CallStatus;
use Vehikl\LaravelTwilioProgrammableVoiceTestRig\ProgrammableVoiceRig;
use Vehikl\LaravelTwilioProgrammableVoiceTestRig\TwimlApp;
use Vehikl\LaravelTwilioProgrammableVoiceTestRig\TwimlAppConfiguration;

class AnonymousDialingTest extends TestCase
{

    /** @test */
    public function itCanGatherDigitsAndMakeAPhoneCall(): void
    {
        (new ProgrammableVoiceRig(
            $this->app,
            new TwimlApp(
                voice: new TwimlAppConfiguration(
                    requestUrl: route('anonymous-dialing.gather'),
                ),
            ),
        ))
            ->ring(from: '15554443322', to: '12223334455')
            ->assertSay('Dial North-American Number, then press pound')
            ->assertGather([
                'action' => route('anonymous-dialing.dial'),
                'input' => 'dtmf',
                'finishOnKey' => '#',
                'numDigits' => 12,
                'actionOnEmptyResult' => false,
            ], [
                '<Say>hi</Say>'
            ])
            ->gatherDigits('5554443322#')
            ->assertRedirect(route('anonymous-dialing.failed'), ['method' => 'POST'])
            ->assertTwilioHit(route('anonymous-dialing.dial'), byTwimlTag: "Gather")
            ->assertSay('Dialing 5 5 5 4 4 4 3 3 2 2, please wait')
            ->assertPause(1)
            ->assertDial('5554443322', ['action' => route('anonymous-dialing.completed'), 'method' => 'POST'])
            ->dial(CallStatus::completed, duration: 30)
            ->assertTwilioHit(route('anonymous-dialing.completed'), byTwimlTag: 'Dial')
            ->assertSay('Phone call completed')
            ->assertHangup()
            ->assertCallEnded();
    }

    /** @test */
    public function itFailsIfProvidedNoGather(): void
    {
        (new ProgrammableVoiceRig(
            $this->app,
            new TwimlApp(
                voice: new TwimlAppConfiguration(
                    requestUrl: route('anonymous-dialing.gather'),
                ),
            ),
        ))
            ->ring(from: '15554443322', to: '12223334455')
            ->assertSay('Dial North-American Number, then press pound')
            //->assertTwimlContains('<Gather action="%s" input="dtmf" finishOnKey="#" numDigits="12" actionOnEmptyResult="false"/>', route('anonymous-dialing.dial'))
            ->assertGather([
                'action' => route('anonymous-dialing.dial'),
                'input' => 'dtmf',
                'finishOnKey' => '#',
                'numDigits' => 12,
                'actionOnEmptyResult' => false,
            ], true)
            ->assertRedirect(route('anonymous-dialing.failed'), ['method' => 'POST'])
            ->assertTwilioHit(route('anonymous-dialing.failed'), byTwimlTag: "Redirect")
            ->assertSay('Please try again later')
            ->assertTwimlContains('<Hangup/>', route('anonymous-dialing.completed'))
            ->assertHangup()
            ->assertCallEnded();
    }


}
