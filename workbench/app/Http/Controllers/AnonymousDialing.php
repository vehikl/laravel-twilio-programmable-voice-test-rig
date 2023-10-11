<?php

namespace Workbench\App\Http\Controllers;

use Illuminate\Http\Request;
use Twilio\TwiML\VoiceResponse;

class AnonymousDialing extends Controller
{
    private VoiceResponse $voiceResponse;

    public function __construct()
    {
        $this->voiceResponse = new VoiceResponse();
    }

    public function gather(): VoiceResponse
    {
        return $this->voiceResponse;
    }

    public function dial(): VoiceResponse
    {
        return $this->voiceResponse;
    }

    public function completed(): VoiceResponse
    {
        return $this->voiceResponse;
    }


}
