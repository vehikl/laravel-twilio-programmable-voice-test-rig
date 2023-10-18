<?php

namespace Vehikl\LaravelTwilioProgrammableVoiceTestRig;

use Exception;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use PHPUnit\Framework\Assert as PHPUnitAssert;
use SimpleXMLElement;
use Throwable;
use Vehikl\LaravelTwilioProgrammableVoiceTestRig\Handlers\TwimlElement;

class ProgrammableVoiceRig
{
    private array $customTwimlHandlers = [];

    protected array $twilioCallParameters = [];

    protected ?Request $request = null;
    protected ?Response $response = null;

    protected array $inputs = [
        'record' => [],
        'gather' => [],
        'dial' => [],
    ];

    protected array $actionableElements = [];

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

    /**
     * @param array<string,mixed> $payload
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

    public function gatherDigits(
        string $digits,
    ): self
    {
        return $this->pushInput('gather', ['Digits' => $digits]);
    }

    public function gatherSpeech(string $speechResult, float $confidence = 1.0): self
    {
        return $this->pushInput('gather', [
            'SpeechResult' => $speechResult,
            'Confidence' => $confidence,
        ]);
    }

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

    public function consumeInput(string $type): ?array
    {
        if (!isset($this->inputs[$type])) {
            throw new Exception("ProgrammableVoiceRig does not support $type input");
        }

        return array_shift($this->inputs[$type]);
    }

    public function peekInput(string $type): ?array
    {
        if (!isset($this->inputs[$type])) {
            throw new Exception("ProgrammableVoiceRig does not support $type input");
        }

        return $this->inputs[$type][0] ?? null;
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
     * @param callable(): mixed $callback
     */
    public function tap(callable $callback): self
    {
        $callback($this->request, $this->response);

        return $this;
    }

    private function parseTwiml(string $twiml): void
    {
        $this->actionableElements = [];

        $xml = '';
        try {
            $xml = simplexml_load_string($twiml);
        } catch (Throwable $e) {
            PHPUnitAssert::fail('Invalid Twiml response');
        }
        if (!$this->isTwiml($xml)) {
            return;
        }

        foreach ($xml->children() as $tag) {
            $element = TwimlElement::fromElement($this, $tag, null, $this->customTwimlHandlers);
            if ($element->isActionable()) {
                $this->actionableElements [] = $element;
            }
        }
    }

    /**
     * @param callable(): mixed $nextAction
     */
    private function handleTwiml(Response $response, callable $nextAction): void
    {
        foreach ($this->actionableElements as $actionable) {
            if ($actionable->runAction($nextAction)) {
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
            ->map(fn($line) => trim($line))
            ->filter(fn($line) => strlen($line) > 0)
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

    public function assertSay(string $text): self
    {
        return $this->assertTwimlContains('<Say>%s</Say>', $text);
    }

    public function assertPlay(string $file): self
    {
        return $this->assertTwimlContains('<Play>%s</Play>', $file);
    }

    public function assertPause(int $seconds): self
    {
        return $this->assertTwimlContains('<Pause length="%d"/>', $seconds);
    }

    protected function makeTag(string $tagName, array $attributes, ?array $children = null): string
    {
        $attrs = [];
        foreach ($attributes as $key => $value) {
            $boolValue = $value ? 'true' : 'false';
            $actualValue = is_bool($value)
                ? $boolValue
                : $value;
            $attrs [] = sprintf('%s="%s"', $key, $actualValue);
        }
        $attrs = implode(' ', $attrs);
        if (($attributes['method'] ?? null) === 'POST') {
            echo "Warning: You have a $tagName with method=\"POST\" but this is the Twiml default, you can remove the method attribute" . PHP_EOL;
        }

        return sprintf(
            '<%s %s%s>%s%s',
            $tagName,
            $attrs,
            $children ? '' : '/',
            $children ? implode('', $children) : '',
            $children ? "</$tagName>" : '',
        );
    }

    /**
     * @param array<string,mixed> $attributes
     */
    public function assertTag(string $tagName, array $attributes, bool $hasChildren = false, string $body = ''): self
    {
        return $this->assertTwimlContains($this->makeTag(
            $tagName,
            $attributes,
            $hasChildren ? [$body] : null
        ));
    }

    public function assertTagWithChildren(string $tagName, array $attributes, array $children = []): self
    {
        return $this->assertTwimlContains($this->makeTag(
            $tagName,
            $attributes,
            $children
        ));
    }

    public function assertDial(string $phoneNumber, array $attributes = []): self
    {
        // return $this->assertTagWithChildren('Dial', $attributes, ['Say', $phoneNumber]);
        return $this->assertTag('Dial', $attributes, true, $phoneNumber);
    }

    /**
     * @param array<string,mixed> $attributes
     */
    public function assertRedirect(string $uri, array $attributes = []): self
    {
        return $this->assertTag('Redirect', $attributes, true);
    }

    /**
     * @param array<string,mixed> $attributes
     */
    public function assertGather(array $attributes = [], ?array $children = null): self
    {
        return $this->assertTagWithChildren('Gather', $attributes, $children);
    }

    public function assertCallStatus(CallStatus|string $expectedCallStatus): self
    {
        $status = is_string($expectedCallStatus)
            ? CallStatus::tryFrom($expectedCallStatus)
            : $expectedCallStatus;

        PHPUnitAssert::assertEquals($status, $this->getCallStatus());

        return $this;
    }

    /**
     * @param array<int,mixed> $tags
     */
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
        foreach ($xml->children() as $tag) {
            $actualTagOrder [] = $tag->getName();
        }
        PHPUnitAssert::assertEquals($tags, $actualTagOrder);

        return $this;
    }

    public function assertTwilioHit(string $expectedUri, string $expectedMethod = 'POST', ?string $byTwimlTag = null): self
    {
        $alreadyHandled = false;
        $this->handleTwiml($this->response, function (string $tag, string $url, string $method = 'POST', array $data = []) use (&$alreadyHandled, $expectedUri, $expectedMethod, $byTwimlTag) {
            if ($alreadyHandled) {
                throw new Exception('Attempted to follow twiml more than once on the same response');
            }

            PHPUnitAssert::assertEquals([$expectedMethod, $expectedUri], [$method, $url]);
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

    public function assertHangUp(): self
    {
        return $this->assertTwimlContains('<Hangup/>');
    }


}
