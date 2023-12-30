<?php

namespace Workbench\App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Twilio\TwiML\VoiceResponse;

class GatherSpeech
{
    const EXTENSIONS = [
        '001#' => 'The CEO',
        '002#' => 'The Manager',
        '003#' => 'The Secretary',
    ];

    private VoiceResponse $response;

    public function __construct()
    {
        $this->response = new VoiceResponse;
    }

    public function prompt(Request $request): VoiceResponse
    {
        $gather = $this->response->gather([
            'action' => route('gather-speech.result'),
            'input' => 'dtmf speech',
            'timeout' => 6,
            'actionOnEmptyResult' => false,
        ]);

        $gather->say('Type the extension followed by the pound symbol or say the name of the person you would like to be connected to');
        $gather->pause(['length' => 3]);
        $gather->say('If you are not sure, say directory');

        $this->response->redirect(route($request->input('on-failure', 'gather-speech.empty')));

        return $this->response;
    }

    public function result(Request $request): VoiceResponse
    {
        $response = $this->resultGetTargetUsingDigits($request)
            ?? $this->resultGetTargetUsingSpeech($request);

        if (!$response) {
            $this->response->redirect(route('gather-speech.empty'));
            return $this->response;
        }
        return $response;
    }

    private function resultDialTarget(string $target): VoiceResponse
    {
        $this->response->say('You will be directed to ' . $target);
        $this->response->dial('15554443322', [
            'action' => route('gather-speech.complete'),
            'record' => 'record-from-answer',
            'recordStatusCallbackEvent' => 'complete',
            'recordStatusCallback' => route('gather-speech.recordStatus'),
        ]);

        return $this->response;
    }

    private function resultGetTargetUsingDigits(Request $request): ?VoiceResponse
    {
        if (!$request->has('Digits')) {
            return null;
        }
        $digits = $request->input('Digits');

        if ($digits === 'hangup') {
            $this->response->hangup();
            return $this->response;
        }

        if (!isset(self::EXTENSIONS[$digits])) {
            return null;
        }

        return $this->resultDialTarget(self::EXTENSIONS[$digits]);
    }

    private function resultGetTargetUsingSpeech(Request $request): ?VoiceResponse
    {
        if (!$request->has('SpeechResult')) {
            return null;
        }

        if ((float)$request->input('Confidence', 0.0) < 0.5) {
            $this->response->redirect(route('gather-speech.empty'));
            return $this->response;
        }

        return $this->resultDialTarget($request->input('SpeechResult'));
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

    public function complete(Request $request): VoiceResponse
    {
        $this->response->hangup();

        return $this->response;
    }

    public function recordStatus(Request $request): JsonResponse
    {
        return new JsonResponse([], JsonResponse::HTTP_NO_CONTENT);
    }
}
