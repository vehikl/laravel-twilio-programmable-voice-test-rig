<?php

namespace Workbench\App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Twilio\TwiML\VoiceResponse;

class MultipleStep extends Controller
{
    private VoiceResponse $voiceResponse;

    public function __construct()
    {
        $this->voiceResponse = new VoiceResponse;
    }

    public function record(Request $request): VoiceResponse
    {
        $this->voiceResponse->say('Record your name');
        $this->voiceResponse->record([
            'action' => route('multiple-step.thanks'),
        ]);
        $this->voiceResponse->redirect(route('multiple-step.emptyRecordingRetry'), ['method' => 'POST']);

        return $this->voiceResponse;
    }

    public function thanks(Request $request): VoiceResponse
    {
        $this->voiceResponse->say('Thank-you for recording your name');
        $this->voiceResponse->hangup();

        return $this->voiceResponse;
    }

    public function emptyRecordingRetry(Request $request): VoiceResponse
    {
        $this->voiceResponse->say('Oops, we couldn\'t hear you, try again');
        $this->voiceResponse->redirect(route('multiple-step.record'), ['method' => 'POST']);
        
        return $this->voiceResponse;
    }
}
