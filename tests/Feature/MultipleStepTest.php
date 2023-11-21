<?php

namespace Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;
use Vehikl\LaravelTwilioProgrammableVoiceTestRig\CallStatus;
use Vehikl\LaravelTwilioProgrammableVoiceTestRig\ProgrammableVoiceRig;
use Vehikl\LaravelTwilioProgrammableVoiceTestRig\TwimlApp;

class MultipleStepTest extends TestCase
{
    /** @test */
    public function itFollowsRecordActionWhenRecordingPresent(): void
    {
        $statusChangeUrl = 'http://localhost/my-status-change';
        Http::fake([
            $statusChangeUrl => Http::response([], 200),
        ]);

        (new ProgrammableVoiceRig($this->app))
            ->phoneCall(
                from: '15554443322',
                to: '12223334455',
                endpoint: new TwimlApp(
                    requestUrl: route('multiple-step.record'),
                    statusCallbackUrl: $statusChangeUrl,
                )
            )
            ->assertSuccessful()
            ->assertValidResponse()
            ->assertSay('Record your name')
            ->assertRecord([
                'action' => route('multiple-step.thanks')
            ])
            ->withAudio(recordingUrl: 'file.mp3', recordingDuration: 5)
            ->assertEndpoint(route('multiple-step.thanks'), 'POST')
            ->assertSay('Thank-you for recording your name')
            ->assertHangup()
            ->assertCallEnded();

        Http::assertSent(function (Request $request) use ($statusChangeUrl) {
           return $request->url() === $statusChangeUrl;
        });
    }

    /** @test */
    public function itSkipsRecordingTwimlWhenNoRecordingGiven(): void
    {
        (new ProgrammableVoiceRig($this->app))
            ->phoneCall(from: '15554443322', to: '12223334455', endpoint: route('multiple-step.record'))
            ->assertSay('Record your name')
            ->assertRecord(['action' => route('multiple-step.thanks')])
            ->withSilence()
            ->assertRedirect(route('multiple-step.emptyRecordingRetry'), 'POST')
            ->assertPlay('sad-trombone.mp3')
            ->assertPause(2)
            ->assertSay('Oops, we couldn\'t hear you, try again')
            ->assertCallStatus(CallStatus::in_progress);
    }
}
