<?php

namespace Workbench\App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Twilio\TwiML\VoiceResponse;

class InvalidFlows
{
    public function json(): JsonResponse
    {
        return new JsonResponse([], 200);
    }

    public function string(): string
    {
        return 'hello world';
    }

    public function redirectNotFound(): VoiceResponse
    {
        $response = new VoiceResponse;
        $response->redirect('/path/does/not/exist');
        return $response;
    }

    public function redirectToServerError(): VoiceResponse
    {
        $response = new VoiceResponse;
        $response->redirect(route('invalid-flows.serverError'));
        return $response;
    }

    public function serverError(): VoiceResponse
    {
        $response = new VoiceResponse;
        throw new \Exception('Server error!');
        return new VoiceResponse;


    }
}
