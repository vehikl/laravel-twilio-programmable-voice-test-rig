<?php

namespace Workbench\App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Twilio\TwiML\VoiceResponse;

class SingleStep extends Controller
{
    public function sayAndHangup(Request $request): VoiceResponse
    {
        $voice = new VoiceResponse();
        $voice->say('Saying something here');

        return $voice;
    }

    public function reject(Request $request): VoiceResponse
    {
        $voice = new VoiceResponse();
        $voice->reject(['reason' => $request->input('reason')]);

        return $voice;
    }

    public function ring(Request $request): VoiceResponse
    {
        
        $voice = new VoiceResponse();
        $voice->pause(['length' => 5]);
        $voice->hangup();

        return $voice;
    }

    public function redirectBlocksOtherInstructions(Request $request): VoiceResponse
    {
        
        $voice = new VoiceResponse();
        $voice->redirect(route('single-step.sayAndHangup'));
        $voice->hangup();

        return $voice;
    }
}
