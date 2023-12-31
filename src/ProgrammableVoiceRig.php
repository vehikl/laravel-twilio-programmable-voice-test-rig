<?php

namespace Vehikl\LaravelTwilioProgrammableVoiceTestRig;

use Closure;
use DOMDocument;
use Exception;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use PHPUnit\Framework\Assert as PHPUnitAssert;
use PHPUnit\Framework\ExceptionWrapper;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Throwable;
use Vehikl\LaravelTwilioProgrammableVoiceTestRig\Handlers\Element;

class ProgrammableVoiceRig extends AssertContext
{
    protected array $twilioCallParameters = [];

    public ?Request $request = null;
    // public ?Response $response = null;
    public ?SymfonyResponse $response = null;

    protected array $inputs = [
        'record' => [],
        'gather' => [],
        'dial' => [],
    ];

    private bool $withWarnings = false;


    public function __construct(protected Application $app, protected TwimlApp $twimlApp, string $direction = 'inbound', CallStatus $callStatus = CallStatus::ringing)
    {
        $this->twilioCallParameters = [
            'AccountSid' => sprintf('AC%s', fake()->uuid),
            'CallSid' => sprintf('CA%s', fake()->uuid),
            'CallStatus' => $callStatus->value,
            'ApiVersion' => '',
            'Direction' => $direction,
        ];
    }

    public function warnings(bool $toggle = true): self
    {
        $this->withWarnings = $toggle;
        return $this;
    }

    public function warn(string $format, mixed ...$replacements): void
    {
        if (!$this->withWarnings) return;
        echo sprintf('WARNING: %s%s', sprintf($format, ...$replacements), PHP_EOL);
    }

    /**
     * @param array<string,mixed> $payload
     * @throws Exception
     */
    private function pushInput(string $type, array $payload): self
    {
        if (!isset($this->inputs[$type])) {
            throw new Exception("ProgrammableVoiceRig does not support $type input");
        }

        $this->inputs[$type] [] = array_filter(
            $payload,
            fn($value) => $value !== null,
        );

        return $this;
    }

    /**
     * @throws Exception
     */
    public function record(
        string  $recordingUrl,
        int     $recordingDuration,
        ?string $digits = null,
    ): self
    {
        return $this->pushInput('record', [
            'RecordingUrl' => $recordingUrl,
            'RecordingDuration' => $recordingDuration,
            'Digits' => $digits
        ]);
    }

    /**
     * @throws Exception
     */
    public function gatherDigits(
        string $digits,
    ): self
    {
        return $this->pushInput('gather', ['Digits' => $digits]);
    }

    /**
     * @throws Exception
     */
    public function gatherSpeech(string $speechResult, float $confidence = 1.0): self
    {
        return $this->pushInput('gather', [
            'SpeechResult' => $speechResult,
            'Confidence' => $confidence,
        ]);
    }

    /**
     * @throws Exception
     */
    public function dial(
        CallStatus $callStatus,
        ?string    $callSid = null,
        int        $duration = 60,
        bool       $bridged = true,
        ?string    $recordingUrl = null,
    ): self
    {
        return $this->pushInput('dial', [
            'DialCallStatus' => $callStatus->value,
            'DialCallSid' => $callSid ?? ('CA' . fake()->uuid),
            'DialCallDuration' => $duration,
            'DialBridged' => $bridged,
            'RecordingUrl' => $recordingUrl,
        ]);
    }

    /**
     * @throws Exception
     */
    public function consumeInput(string $type): ?array
    {
        if (!isset($this->inputs[$type])) {
            throw new Exception("ProgrammableVoiceRig does not support $type input");
        }

        return array_shift($this->inputs[$type]);
    }

    private function wrapPhoneNumber(PhoneNumber|string $phoneNumber): PhoneNumber
    {
        return is_string($phoneNumber)
            ? new PhoneNumber($phoneNumber)
            : $phoneNumber;
    }

    public function from(PhoneNumber|string $phoneNumber): self
    {
        $this->twilioCallParameters = array_merge(
            $this->twilioCallParameters,
            $this->wrapPhoneNumber($phoneNumber)->toParameters('From'),
        );
        return $this;
    }

    public function to(PhoneNumber|string $phoneNumber): self
    {
        $this->twilioCallParameters = array_merge(
            $this->twilioCallParameters,
            $this->wrapPhoneNumber($phoneNumber)->toParameters('To'),
        );
        return $this;
    }

    public function forwardedFrom(string|PhoneNumber|null $phoneNumber): self
    {
        if ($phoneNumber === null) {
            return $this;
        }

        $this->twilioCallParameters = array_merge(
            $this->twilioCallParameters,
            $this->wrapPhoneNumber($phoneNumber)->toParameters('ForwardedFrom'),
        );
        return $this;
    }


    /**
     * @param array<int,mixed> $extraData
     * @throws Exception
     */
    private function handleRequest(string $url, string $method = 'POST', array $extraData = [], bool $skipNavigation = false): Response
    {
        $parameters = [...$this->twilioCallParameters, ...$extraData];

        $request = Request::create($url, $method, $parameters);
        $response = $this->app->handle($request);

        if (!$skipNavigation) {
            $this->request = $request;
            $this->response = $response;
            $this->parseTwiml($response->getContent());
        }

        return $response;
    }


    /**
     * @throws Exception
     */
    public function setCallStatus(CallStatus|string $callStatus): void
    {
        $status = is_string($callStatus)
            ? CallStatus::tryFrom($callStatus)
            : $callStatus;

        if ($this->twilioCallParameters['CallStatus'] === $status->value) {
            return;
        }
        $this->twilioCallParameters['CallStatus'] = $status->value;
        if ($this->twimlApp->voice->statusCallbackUrl) {
            $this->handleRequest($this->twimlApp->voice->statusCallbackUrl, $this->twimlApp->voice->statusCallbackMethod, skipNavigation: true);
        }
    }

    /**
     * @throws Exception
     */
    public function ring(string|PhoneNumber $from, string|PhoneNumber $to, string|PhoneNumber|null $forwardedFrom = null): self
    {
        $this->from($from)->to($to)->forwardedFrom($forwardedFrom);
        $voiceApp = $this->twimlApp->voice;

        if (!$voiceApp) {
            PHPUnitAssert::fail('Unable to make a call, voice app not configured');
        }

        $this->setCallStatus(CallStatus::in_progress);

        $this->response = $this->handleRequest($this->twimlApp->voice->requestUrl, $this->twimlApp->voice->requestMethod);

        return $this;
    }

    /**
     * @param Closure(Request $req, Response): void $callback
     */
    public function tap(Closure $callback): self
    {
        $callback($this->request, $this->response);

        return $this;
    }

    private function parseTwiml(string $twiml): void
    {
        $doc = new DOMDocument();
        try {
            if (!$doc->loadXML($twiml)) {
                PHPUnitAssert::fail(sprintf(
                    'Invalid Twiml response from %s:%s%s',
                    $this->request->url(),
                    PHP_EOL,
                    $twiml,
                ));
            }
        } catch (Throwable $e) {
            PHPUnitAssert::fail(sprintf(
                'Invalid Twiml response from %s%s %s%s%s%s%s%s%s',
                PHP_EOL,
                $this->request->method(),
                $this->request->method() === 'GET' ? '' : $this->request->url(),
                PHP_EOL,
                json_encode($this->request->all(), JSON_PRETTY_PRINT),
                PHP_EOL,
                $twiml,
                PHP_EOL,
                $e->getMessage(),
            ));
            throw $e;
        }
        $this->isTwiml($doc);
        $this->setAssertionContext($doc);
    }

    /**
     * @param Closure(string, string, string, array):void $nextAction
     */
    private function handleTwiml(Closure $nextAction): void
    {
        $topLevelElements = $this->root->firstChild->childNodes;

        foreach ($topLevelElements as $child) {
            $handler = Element::fromElement($this, $child);
            if (!$handler->isActionable()) continue;
            if ($handler->runAction($nextAction)) {
                return;
            }

        }
        $this->setCallStatus('completed');
    }

    private function isTwiml(DOMDocument $doc): void
    {
        PHPUnitAssert::assertEquals('Response', $doc->firstChild->nodeName);
    }

    public function assertTwilioHit(string $expectedUri, string $expectedMethod = 'POST', ?string $byTwimlTag = null): self
    {
        $alreadyHandled = false;
        $this->handleTwiml(function (string $tag, string $url, string $method = 'POST', array $data = []) use (&$alreadyHandled, $expectedUri, $expectedMethod, $byTwimlTag) {
            if ($alreadyHandled) {
                throw new Exception('Attempted to follow twiml more than once on the same response');
            }

            PHPUnitAssert::assertEquals("$expectedMethod $expectedUri", "$method $url", 'Unexpected twilio redirect');
            if ($byTwimlTag) {
                PHPUnitAssert::assertEquals($byTwimlTag, $tag);
            }

            $this->response = $this->handleRequest($url, $method, $data);
            $alreadyHandled = true;
        });
        if (!$alreadyHandled) {
            PHPUnitAssert::fail("Expected redirect to $expectedMethod $expectedUri, but no action or redirect found in twiml");
        }
        return $this;
    }

    public function assertCallEnded(): self
    {
        $this->handleTwiml(function (string $url, string $method = 'POST', array $data = []) use (&$alreadyHandled) {
            PHPUnitAssert::fail("Call did not complete, and was redirected to $method $url");
        });

        PHPUnitAssert::assertContains($this->getCallStatus(), [
            CallStatus::busy,
            CallStatus::failed,
            CallStatus::canceled,
            CallStatus::completed,
            CallStatus::no_answer
        ]);

        return $this;
    }
}
