<?php

namespace Vehikl\LaravelTwilioProgrammableVoiceTestRig;

use Exception;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use PHPUnit\Framework\Assert as PHPUnitAssert;
use ReflectionClass;
use SimpleXMLElement;
use Throwable;
use Vehikl\LaravelTwilioProgrammableVoiceTestRig\Handlers\Gather;
use Vehikl\LaravelTwilioProgrammableVoiceTestRig\Handlers\Hangup;
use Vehikl\LaravelTwilioProgrammableVoiceTestRig\Handlers\Record;
use Vehikl\LaravelTwilioProgrammableVoiceTestRig\Handlers\Redirect;
use Vehikl\LaravelTwilioProgrammableVoiceTestRig\Handlers\TwimlHandler;

class ProgrammableVoiceRig
{
    const TWIML_HANDLERS = [
        'Redirect' => Redirect::class,
        'Record' => Record::class,
        'Gather' => Gather::class,
        'Hangup' => Hangup::class,
    ];

    private array $customTwimlHandlers = [];

    private array $twilioCallParameters = [];
    private ?Response $response = null;
    private array $inputQueue = [];
    private array $dialQueue = [];

    private ?Request $request = null;

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

    public function setCustomTwimlHandler(string $tag, ?string $classReference): self
    {
        if (!$classReference) {
            unset($this->customTwimlHandlers[$tag]);
        } else {
            $this->customTwimlHandlers[$tag] = $classReference;
        }
        return $this;
    }

    /**
     * @throws \ReflectionException
     */
    private function getTwimlHandler(string $tag): ?TwimlHandler
    {
        $classRef = $this->customTwimlHandlers[$tag] ?? self::TWIML_HANDLERS[$tag] ?? null;
        if (!$classRef) {
            return null;
        }
        return (new ReflectionClass($classRef))->newInstance();
    }

    public function from(PhoneNumber|string $phoneNumber): self
    {
        $wrapped = is_string($phoneNumber)
            ? new PhoneNumber($phoneNumber)
            : $phoneNumber;

        $this->twilioCallParameters = array_merge(
            $this->twilioCallParameters,
            $wrapped->toParameters('From'),
        );
        return $this;
    }

    public function to(PhoneNumber|string $phoneNumber): self
    {
        $wrapped = is_string($phoneNumber)
            ? new PhoneNumber($phoneNumber)
            : $phoneNumber;
        $this->twilioCallParameters = array_merge(
            $this->twilioCallParameters,
            $wrapped->toParameters('To'),
        );
        return $this;
    }

    public function forwardedFrom(string|PhoneNumber|null $phoneNumber): self
    {
        if ($phoneNumber === null) {
            return $this;
        }
        $wrapped = is_string($phoneNumber)
            ? new PhoneNumber($phoneNumber)
            : $phoneNumber;

        $this->twilioCallParameters = array_merge(
            $this->twilioCallParameters,
            $wrapped->toParameters('ForwardedFrom'),
        );
        return $this;
    }

    public function record(
        ?string $recordingUrl = null,
        ?int $recordingDuration = null,
        ?string $digits = null,
    ): self
    {
        $input = [];
        if ($recordingUrl !== null) {
            $input['RecordingUrl'] = $recordingUrl;
        }
        if ($recordingDuration !== null) {
            $input['RecordingDuration'] = $recordingDuration;
        }
        if ($digits !== null) {
            $input['Digits'] = $digits;
        }
        $this->inputQueue [] = $input;

        return $this;
    }

    public function gather(
        ?string $digits = null,
        ?string $speechResult = null,
        ?string $confidence = null
    ): self
    {
//        $input = [];
//        if ($recordingUrl !== null) {
//            $input['RecordingUrl'] = $recordingUrl;
//        }
//        if ($recordingDuration !== null) {
//            $input['RecordingDuration'] = $recordingDuration;
//        }
//        if ($digits !== null) {
//            $input['Digits'] = $digits;
//        }
//        $this->inputQueue [] = $input;

        return $this;
    }

    public function queueDial(string $dialCallStatus = 'completed', ?string $dialCallSid = null, int $dialCallDuration = 120, bool $dialBridged = false, ?string $recordingUrl = null): self
    {
        $dial = [
            'DialCallStatus' => $dialCallStatus,
            'DialCallSid' => $dialCallSid,
            'DialCalLDuration' => $dialCallDuration,
            'DialBridged' => $dialBridged,
        ];
        if ($recordingUrl) {
            $dial['RecordingUrl'] = $recordingUrl;
        }
        $this->dialQueue [] = $dial;
        return $this;
    }

    public function shiftInput(): ?array
    {
        return array_shift($this->inputQueue);
    }

    public function shiftDial(): array
    {
        return array_shift($this->dialQueue) ?? [
            'DialCallStatus' => 'completed',
            'DialCallSid' => 'CA' . fake()->uuid,
            'DialCalLDuration' => 0,
            'DialBridged' => false,
        ];
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

    public function assert(Callable $assertCallback): self
    {
        $assertCallback(new Assert($this));
        return $this;
    }

    public function getRequest(): ?Request
    {
        return $this->request;
    }

    public function getResponse(): ?Response
    {
        return $this->response;
    }

    public function assertRedirectedTo(string $expectedUri, string $expectedMethod = 'POST'): self
    {
        $alreadyHandled = false;
        $this->handleTwiml($this->response, function (string $url, string $method = 'POST', array $data = []) use (&$alreadyHandled, $expectedUri, $expectedMethod) {
            if ($alreadyHandled) {
                throw new Exception('Attempted to follow twiml more than once on the same response');
            }

            PHPUnitAssert::assertEquals([$expectedMethod, $expectedUri], [$method, $url]);

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
        $this->handleTwiml($this->response, function (string $url, string $method = 'POST', array $data = []) use (&$alreadyHandled) {
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

    public function tap(Callable $callback): self
    {
        $callback($this->request, $this->response);

        return $this;
    }

    private function handleTwiml(Response $response, Callable $nextAction): void
    {
        $content = $response->getContent();
        $xml = '';
        try {
            $xml = simplexml_load_string($content);
        } catch (Throwable $e) {
            PHPUnitAssert::fail('Invalid Twiml response');
        }
        if (!$this->isTwiml($xml)) {
            return;
        }

        foreach($xml->children() as $tag) {
            $handler = $this->getTwimlHandler($tag->getName());
            if ($handler?->handle($this, $tag, $nextAction)) {
                return;
            }
        }

        $this->setCallStatus('completed');
    }

    private function isTwiml(SimpleXMLElement $xml): bool
    {
        return $xml->getName() === 'Response';
    }

    public function getCallStatus(): CallStatus
    {
        return CallStatus::tryFrom($this->twilioCallParameters['CallStatus']);
    }

    public function twiml(): string
    {
        return $this->response->getContent();
    }

    /**
     * @param mixed $replacements
     */
    protected function normalizeTwiml(string $xml, ...$replacements): string
    {
        $normalized = collect(explode("\n", sprintf($xml, ...$replacements)))
            ->map(fn ($line) => trim($line))
            ->filter(fn ($line) => strlen($line) > 0)
            ->join("");

        return str_replace('&', '&amp;', $normalized);
    }

    /**
     * @param mixed $replacements
     */
    public function assertTwimlEquals(string $xml, ...$replacements): self
    {
        $expectedTwiml = sprintf(
            "%s\n<Response>%s</Response>\n",
            '<?xml version="1.0" encoding="UTF-8"?>',
            $this->normalizeTwiml($xml, ...$replacements),
        );

        PHPUnitAssert::assertEquals($expectedTwiml, $this->twiml(), 'Expected twiml does not match actual');

        return $this;
    }
    /**
     * @param mixed $replacements
     */
    public function assertTwimlContains(string $xml, ...$replacements): self
    {
        $expectedPartialTwiml = $this->normalizeTwiml($xml, ...$replacements);

        PHPUnitAssert::assertStringContainsString($expectedPartialTwiml, $this->twiml(), 'Expected twiml does not match actual');

        return $this;
    }

    public function assertCallStatus(CallStatus|string $expectedCallStatus): self
    {
        $status = is_string($expectedCallStatus)
            ? CallStatus::tryFrom($expectedCallStatus)
            : $expectedCallStatus;

        PHPUnitAssert::assertEquals($status, $this->getCallStatus());

        return $this;
    }

    public function assertSaid(string $text): self
    {
        PHPUnitAssert::assertStringContainsString("<Say>$text</Say>", $this->twiml());

        return $this;
    }

    public function assertTwimlOrder(array $tags): self
    {
        $xml = null;
        try {
            $xml = simplexml_load_string($this->response->getContent());
        } catch (Throwable $e) {
            PHPUnitAssert::fail('Invalid Twiml response');
        }
        if (!$this->isTwiml($xml)) {
            return $this;
        }

        $actualTagOrder = [];
        foreach($xml->children() as $tag) {
            $actualTagOrder [] = $tag->getName();
        }
        PHPUnitAssert::assertEquals($tags, $actualTagOrder);

        return $this;
    }

    public function assertPlayed($file): self
    {
        PHPUnitAssert::assertStringContainsString("<Play>$file</Play>", $this->twiml());

        return $this;
    }

    public function assertPaused($seconds): self
    {
        PHPUnitAssert::assertStringContainsString("<Pause length=\"$seconds\"/>", $this->twiml());

        return $this;
    }
}
