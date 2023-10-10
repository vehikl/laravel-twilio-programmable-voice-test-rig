<?php

namespace Workbench\App\Http\Controllers;

use Illuminate\Http\Request;
use Twilio\TwiML\VoiceResponse;

class First extends Controller
{
    public function stepOne()
    {
        $voice = new VoiceResponse();
        $voice->say('Saying something here');

        return $voice;
    }
}
