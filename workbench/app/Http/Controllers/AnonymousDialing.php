<?php

namespace Workbench\App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
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
        $this->voiceResponse->say('Dial North-American Number, then press pound');
        $this->voiceResponse->gather([
            'action' => route('anonymous-dialing.dial'),
            'input' => 'dtmf',
            'finishOnKey' => '#',
            'numDigits' => 12,
            'actionOnEmptyResult' => false,

        ]);
        $this->voiceResponse->redirect(route('anonymous-dialing.failed'), ['method' => 'POST']);
        return $this->voiceResponse;
    }

    public function dial(Request $request): VoiceResponse
    {
        $digits = preg_replace('/\D/g', '', $request->input('Digits'));
        if (strlen($digits) === 0) {
            $this->voiceResponse->redirect(route('anonymous-dialing.failed'), ['method' => 'POST']);
            return $this->voiceResponse;
        }

        $digitsSplit = implode(' ', explode('', $digits));

        $this->voiceResponse->say("Dialing $digitsSplit, please wait");
        $this->voiceResponse->pause(['length' => 1]);

        $this->voiceResponse->dial($digits, [
            'action' => route('anonymous-dialing.completed'),
            'method' => 'POST',
        ]);

        return $this->voiceResponse;
    }

    public function completed(): VoiceResponse
    {
        $this->voiceResponse->say('Phone call completed');
        $this->voiceResponse->hangup();

        return $this->voiceResponse;
    }

    public function failed(): VoiceResponse
    {
        $this->voiceResponse->say('Please try again later');
        $this->voiceResponse->hangup();
        return $this->voiceResponse;
    }

}
