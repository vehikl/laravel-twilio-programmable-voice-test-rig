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
}
