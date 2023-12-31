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
        $gather = $this->voiceResponse->gather([
            'action' => route('anonymous-dialing.dial'),
            'input' => 'dtmf',
            'finishOnKey' => '#',
            'numDigits' => 12,
            'actionOnEmptyResult' => false,
        ]);
        $gather->say('hi');
        $this->voiceResponse->redirect(
            route('anonymous-dialing.failed'),
            ['method' => 'POST']
        );

        /*
         <?xml 1.0?>
        <Response>
            <Say>yo wuddup</Say>
            <Gather action="https://site.com/anonymous-dailing/dial" input="dtmf" finishOnKey="#" ..../>
            <Redirect .../>
        </Response>

         */

        return $this->voiceResponse;
    }

    public function dial(Request $request): VoiceResponse
    {
        $digits = preg_replace('/\D/', '', $request->input('Digits'));
        if (strlen($digits) === 0) {
            $this->voiceResponse->redirect(route('anonymous-dialing.failed'), ['method' => 'POST']);
            return $this->voiceResponse;
        }

        $digitsSplit = implode(' ', str_split($digits));

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
