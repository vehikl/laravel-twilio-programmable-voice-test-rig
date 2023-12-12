<?php

namespace Tests\Feature;

use Tests\TestCase;
use Vehikl\LaravelTwilioProgrammableVoiceTestRig\ProgrammableVoiceRig;
use Workbench\App\Http\Controllers\GatherSpeech;

class GatherSpeechTest extends TestCase
{
    /**
     * @test
     */
    public function itGoesThroughTheGatherSpeechFlow(): void
    {
        (new ProgrammableVoiceRig(app: $this->app))
            ->from('15554443322')
            ->to('19998887766')
            ->phoneCall(endpoint: route('gather-speech.prompt'))

            ->assertGather([
                'action' => route('gather-speech.result'),
                'input' => 'dtmf speech',
                'timeout' => 6,
                'actionOnEmptyResult' => false,
            ])
            ->assertChildren(function (ProgrammableVoiceRig $asserter) {
                return $asserter
                    ->assertSay('Type the extension followed by the pound symbol or say the name of the person you would like to be connected to')
                    ->assertPause(3)
                    ->assertSay('If you are not sure, say directory');
            })
            ->withSilence()
            ->assertRedirect(route('gather-speech.empty'))

            ->assertEndpoint(route('gather-speech.empty'))
            ->assertSay('I did not catch that, please try again')
            ->assertRedirect(route('gather-speech.prompt', ['on-failure' => 'gather-speech.fail']))

            ->assertEndpoint(route('gather-speech.prompt', ['on-failure' => 'gather-speech.fail']))
            ->assertGather([
                'action' => route('gather-speech.result'),
            ])
            ->withSpeech(speechResult: 'Test', confidence: 1.0, digits: '')

            ->assertEndpoint(route('gather-speech.result'))
            ->assertSay('You will be directed to Test')
            ->assertDial('15554443322')
            ->withAnswer()

            ->assertEndpoint(route('gather-speech.complete'))
            ->assertHangup()
            ->assertCallEnded();
    }

    /**
     * @test
     */
    public function itGoesThroughTheGatherDigits(): void
    {
        (new ProgrammableVoiceRig(app: $this->app))
            ->from('15554443322')
            ->to('19998887766')
            ->phoneCall(endpoint: route('gather-speech.prompt'))

            ->assertGather([
                'action' => route('gather-speech.result'),
                'input' => 'dtmf speech',
                'timeout' => 6,
                'actionOnEmptyResult' => false,
            ])
            ->assertChildren(function (ProgrammableVoiceRig $asserter) {
                return $asserter
                    ->assertSay('Type the extension followed by the pound symbol or say the name of the person you would like to be connected to')
                    ->assertPause(3)
                    ->assertSay('If you are not sure, say directory');
            })
            ->withDigits('002#')

            ->assertEndpoint(route('gather-speech.result'))
            ->assertSay('You will be directed to ' . GatherSpeech::EXTENSIONS['002#'])
            ->assertDial('15554443322')
            ->withBusy()

            ->assertEndpoint(route('gather-speech.complete'))
            ->assertHangup()
            ->assertCallEnded();
    }
}
