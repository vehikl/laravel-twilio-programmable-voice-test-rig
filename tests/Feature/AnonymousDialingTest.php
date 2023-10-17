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
            ->assertTwimlContains('<Gather action="%s" input="dtmf" finishOnKey="#" numDigits="12" actionOnEmptyResult="false"/>', route('anonymous-dialing.dial'))
//            ->assertTwimlContains('<Redirect method="POST">%s</Redirect>', route('anonymous-dialing.failed'))
            ->gatherDigits('5554443322#')
            ->assertRedirected(route('anonymous-dialing.failed'), 'POST')
            ->assertTwilioHit(route('anonymous-dialing.dial'), byTwimlTag: "Gather")
            ->assertSay('Dialing 5 5 5 4 4 4 3 3 2 2, please wait')
            ->assertPause(1)
            ->assertTwimlContains('<Dial action="%s" method="POST">5554443322</Dial>', route('anonymous-dialing.completed'))
            ->dial(CallStatus::completed, duration: 30)

            ->assertTwilioHit(route('anonymous-dialing.completed'), byTwimlTag: 'Dial')
            ->assertSay('Phone call completed')
//            ->assertHungUp()
            ->assertCallEnded();
    }
}
