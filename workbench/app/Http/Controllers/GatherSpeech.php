<?php

namespace Workbench\App\Http\Controllers;

use Illuminate\Http\Request;
use Twilio\TwiML\VoiceResponse;

class GatherSpeech
{
    private VoiceResponse $response;

    public function __construct()
    {
        $this->response = new VoiceResponse;
    }

    public function prompt(Request $request): VoiceResponse
    {
        $gather = $this->response->gather([
            'action' => route('gather-speech.result'),
            'input' => 'speech',
            'timeout' => 6,
            'actionOnEmptyResult' => false,
        ]);

        $gather->say('Say the name of the person you would like to be connected to');
        $gather->pause(['length' => 3]);
        $gather->say('If you are not sure, say directory');

        $this->response->redirect(route($request->input('on-failure', 'gather-speech.empty')));

        return $this->response;
    }

    public function result(Request $request): VoiceResponse
    {
        if ((float)$request->input('Confidence', 0.4) < 0.5) {
            $this->response->redirect(route('gather-speech.empty'));
            return $this->response;
        }

        $this->response->say('You will be directed to ' . $request->input('SpeechResult', 'nobody in particular'));
        $this->response->dial('15554443322');
        $this->response->hangup();

        return $this->response;
    }

    public function empty(): VoiceResponse
    {
        $this->response->say('I did not catch that, please try again');
        $this->response->redirect(route('gather-speech.prompt', ['on-failure' => 'gather-speech.fail']));

        return $this->response;
    }

    public function fail(): VoiceResponse
    {
        $this->response->say('Sorry, I cannot help you at this time, goodbye');
        $this->response->hangup();

        return $this->response;
    }
}
