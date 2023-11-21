<?php

namespace Vehikl\LaravelTwilioProgrammableVoiceTestRig;

use Closure;
use DOMDocument;
use DOMElement;
use DOMNamedNodeMap;
use Exception;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use Vehikl\LaravelTwilioProgrammableVoiceTestRig\Events\DialEvent;
use Vehikl\LaravelTwilioProgrammableVoiceTestRig\Events\GatherEvent;
use Vehikl\LaravelTwilioProgrammableVoiceTestRig\Events\RecordingEvent;
use Vehikl\LaravelTwilioProgrammableVoiceTestRig\Handlers\Element;

class ProgrammableVoiceRig
{
    protected array $parameters = [];

    public string $requestedUri = '/';
    public string $requestedMethod = 'POST';
    public array $requestedBody  = [];
    public ?Response $response = null;
    protected ?DOMDocument $document = null;

    public ?Element $previous = null;
    public ?Element $current = null;

    protected ?TwimlApp $twimlApp = null;

    protected array $inputs = [
        'record' => [],
        'gather' => [],
        'dial' => [],
    ];

    /**
     * @param array<int,mixed> $defaultParameters
     */
    public function __construct(protected Application $app, ?string $accountSid = null, ?string $callSid = null)
    {
        $this->parameters = [
            'AccountSid' => $accountSid ?? sprintf('AC%s', fake()->uuid),
            'CallSid' => $callSid ?? sprintf('CA%s', fake()->uuid),
            'CallStatus' => CallStatus::ringing->value,
            'ApiVersion' => '2010-04-01',
            'Direction' => 'inbound',
        ];
    }

    private function wrapPhoneNumber(PhoneNumber|string $phoneNumber): PhoneNumber
    {
        return is_string($phoneNumber)
            ? new PhoneNumber($phoneNumber)
            : $phoneNumber;
    }

    public function from(PhoneNumber|string $phoneNumber): self
    {
        if ($phoneNumber === null) {
            return $this;
        }

        $this->parameters = array_merge(
            $this->parameters,
            $this->wrapPhoneNumber($phoneNumber)->toParameters('From'),
        );
        return $this;
    }

    public function to(PhoneNumber|string $phoneNumber): self
    {
        if ($phoneNumber === null) {
            return $this;
        }

        $this->parameters = array_merge(
            $this->parameters,
            $this->wrapPhoneNumber($phoneNumber)->toParameters('To'),
        );
        return $this;
    }

    public function forwardedFrom(string|PhoneNumber|null $phoneNumber): self
    {
        if ($phoneNumber === null) {
            return $this;
        }

        $this->parameters = array_merge(
            $this->parameters,
            $this->wrapPhoneNumber($phoneNumber)->toParameters('ForwardedFrom'),
        );
        return $this;
    }

    protected function initializeFromDocument(string|DOMDocument $xml): ?DOMElement
    {
        if (is_string($xml)) {
            $document = new DOMDocument();
            try {
                if ($document->loadXML($xml)) {
                    return $this->initializeFromDocument($document);
                }
            } catch (Throwable $exception) {
                return null;
            }
        }
        $this->document = $xml;
        return $xml->firstElementChild;
    }

    /**
     * @param array<string,mixed> $body
     */
    public function navigate(string $url, string $method, array $body = []): self
    {
        $parameters = [...$this->parameters, ...$body];
        $request = $this->makeRequest($url, $method, $parameters);
        $this->response = $this->getResponse($request);

        if ($this->response->getStatusCode() >= 500 && $this->twimlApp->fallbackUrl) {
            $request = $this->makeRequest($this->twimlApp->fallbackUrl, $this->twimlApp->fallbackMethod, [...$this->parameters, ...$body]);
            $this->response = $this->getResponse($request);
        }

        if ($this->response->isOk()) {
            return $this->navigatedTo(
                $url,
                $method,
                $body,
                $this->initializeFromDocument($this->response->getContent()),
            );
        }

        return $this;
    }

    /**
     * @param array<string,mixed> $body
     */
    public function navigatedTo(string $url, string $method, array $body = [], ?DOMElement $current = null): self
    {
        $this->previous = $this->current;
        $this->current = $current ? Element::fromElement($this, $current) : null;
        $this->requestedUri = $url;
        $this->requestedMethod = $method;
        $this->requestedBody = $body;
        
        return $this;
    }

    /**
     * @param array<string,mixed> $body
     */
    private function makeRequest(string $url, string $method, array $body = []): Request
    {
        $parameters = [...$this->parameters, ...$body];

        return Request::create($url, $method, $parameters);
    }
    

    private function getResponse(Request $request): Response
    {
        return $this->app->handle($request);
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

    public function assertSuccessful(): self
    {
        Assert::assertNotNull($this->response, 'Request not sent');
        Assert::assertTrue($this->response->isOk(), 'Request was not successful');
        return $this;
    }

    public function assertFailure(): self
    {
        Assert::assertFalse($this->response?->isOk(), 'Request was successful');
        return $this;
    }
    
    /**
     * @throws Exception
     */
    public function setCallStatus(CallStatus $callStatus): self
    {
        if ($this->parameters['CallStatus'] == $callStatus->value) {
            return $this;
        }

        $this->parameters['CallStatus'] = $callStatus->value;
        if ($this->twimlApp?->statusCallbackUrl) {
            $this->getResponse($this->makeRequest(
                $this->twimlApp->statusCallbackUrl,
                $this->twimlApp->statusCallbackMethod
            ));
        }

        return $this;
    }

    /**
     * @throws Exception
     */
    public function phoneCall(
        string|PhoneNumber|null $from = null,
        string|PhoneNumber|null $to = null,
        string|PhoneNumber|null $forwardedFrom = null,
        string|TwimlApp|null $endpoint = null,
    ): self
    {
        if ($from) {
            $this->from($from);
        }
        if ($to) {
            $this->to($to);
        }
        if ($forwardedFrom) {
            $this->forwardedFrom($forwardedFrom);
        }

        Assert::assertNotNull($endpoint, 'phoneCall must provide an endpoint');

        $this->twimlApp = is_string($endpoint)
            ? new TwimlApp($endpoint)
            : $endpoint;

        return $this->navigate(
            $this->twimlApp->requestUrl,
            $this->twimlApp->requestMethod,
        );
    }

    /**
     * @param Closure(ProgrammableVoiceRig):void $callback
     */
    public function tap(Closure $callback): self
    {
        $callback($this);

        return $this;
    }

    public function assertCallEnded(): self
    {
        Assert::assertNull($this->current?->nextElement(), 'Call has not ended, there is still twiml to execute');

        return $this;
    }

    public function assertRejected(?Rejection $reason = null): self
    {
        Assert::assertEquals($this->parameters['Direction'] ?? '', 'inbound', 'Twilio does not allow rejections unless the call is inbound');
        $attributes = [];
        if ($reason) {
            $attributes['reason'] = $reason->value;
        }
        return $this->assertNextElement('Reject', $attributes);
    }

    public function assertNotInService(): self
    {
        return $this->assertRejected(Rejection::rejected);
        
    }

    public function assertBusy(): self
    {
        return $this->assertRejected(Rejection::busy);
    }

    private function domAttributesToArray(DOMNamedNodeMap $attributes): array
    {
        $attrs = [];
        foreach ($attributes->getIterator() as $attribute) {
            /** @var DOMAttr $attribute */
            $attrs[$attribute->nodeName] = $attribute->nodeValue;
        }
        return $attrs;
    }

    /**
     * @param array<string,mixed> $expectedAttributes
     */
    private function allAttributesExist(array $expectedAttributes, DOMNamedNodeMap $attributes): bool
    {
        $domAttrs = $this->domAttributesToArray($attributes);
        foreach ($expectedAttributes as $key => $value) {
            $domValue = $domAttrs[$key] ?? null;
            $expectedValue = is_bool($value)
                ? ($value ? 'true' : 'false')
                : $value;
            if ($domValue != $expectedValue) {
                return false;
            }
        }
        return true;
    }

    public function assertMoreTwiml(bool $expectMore): self
    {
        if ($expectMore) {
            Assert::assertNotNull($this->current?->nextElement(), 'Expected more twiml to process, but none left');
        } else {
            Assert::assertNull($this->current?->nextElement(), 'Expected no more twiml to process, but there is more');
        }
        return $this;
    }

    protected function advanceTwiml(): self
    {
        $element = $this->current?->nextElement();

        if (!$element) {
            $this->current = null;
            return $this;
        }

        $this->current = Element::fromElement($this, $element);
        $this->setCallStatus(
            $this->current->callStatus(CallStatus::tryFrom($this->parameters['CallStatus']))
        );

        return $this;
    }
    

    /**
     * @param array<string,mixed> $attributes
     */
    public function assertNextElement(string $tagName, array $attributes = [], bool $exactAttributes = false): self
    {
        $this->advanceTwiml();

        Assert::assertEquals($tagName, $this->current->element->tagName);
        Assert::assertTrue($this->allAttributesExist($attributes, $this->current->element->attributes), 'Missing attributes on ' . $tagName);
        if ($exactAttributes) {
            Assert::assertEquals(count($attributes), $this->current->element->attributes->count(), 'Attributes are not an exact match');
        }
        return $this;
    }

    public function assertTextContent(string $body): self
    {
        Assert::assertEquals($body, $this->current?->element?->textContent);
        return $this;
    }

    public function assertTextContentContains(string $body): self
    {
        Assert::assertStringContainsString($body, $this->current?->element?->textContent);
        return $this;
    }

    /**
     * @param Closure(ProgrammableVoiceTestRig):void $asserter
     */
    public function assertElementChildren(Closure $asserter): self
    {
        $nop = $this->document->createElement('Nop');
        $this->current->element->prepend($nop);
        $context = (new ProgrammableVoiceRig($this->app, $this->parameters['AccountSid'], $this->parameters['CallSid']))
            ->navigatedTo($this->requestedUri, $this->requestedMethod, $this->requestedBody, $nop);
        ;

        $asserter($context);

        $nop->remove();

        return $this;
    }

    public function assertSay(string $text): self
    {
        return $this->assertNextElement('Say', [], true)
            ->assertTextContent($text);
    }

    public function assertPlay(string $file): self
    {
        return $this->assertNextElement('Play', [])
            ->assertTextContent($file);
    }

    public function assertPause(int $seconds): self
    {
        return $this->assertNextElement('Pause', ['length' => $seconds], true);
    }

    /**
     * @param array<int,mixed> $attributes
     */
    public function assertDial(string $phoneNumber, array $attributes = [], bool $exactAttributes = false): DialEvent
    {
        return new DialEvent(
            $this
                ->assertNextElement('Dial', $attributes, $exactAttributes)
                ->assertTextContent($phoneNumber),
            $this->current,
        );
    }

    /**
     * @param array<string,mixed> $attributes
     */
    public function assertRedirect(string $uri, string $method = 'POST', bool $exactAttributes = false): self
    {
        return $this
            ->assertNextElement('Redirect', ['method' => $method], $exactAttributes)
            ->assertTextContent($uri)
            ->navigate($this->current->text(), $this->current->attr('method', 'POST'));
    }

    /**
     * @param array<string,mixed> $attributes
     */
    public function assertGather(array $attributes = [], bool $exactAttributes = false): GatherEvent
    {
        return new GatherEvent(
            $this->assertNextElement('Gather', $attributes, $exactAttributes),
            $this->current,
        );
    }

    /**
     * @param array<string,mixed> $attributes
     */
    public function assertRecord(array $attributes = [], bool $exactAttributes = false): RecordingEvent
    {
        return new RecordingEvent(
            $this->assertNextElement('Record', $attributes, $exactAttributes),
            $this->current,
        );
    }

    public function assertCallStatus(CallStatus $expectedCallStatus): self
    {
        Assert::assertEquals($expectedCallStatus->value, $this->parameters['CallStatus']);

        return $this;
    }

    public function assertHangup(): self
    {
        return $this->assertNextElement('Hangup');
    }

    public function assertEndpoint(string $uri, string $method = 'POST'): self
    {
        Assert::assertEquals($uri, $this->requestedUri, sprintf('Expected endpoint to be %s, but it was %s', $uri, $this->requestedUri));
        Assert::assertEquals($method, $this->requestedMethod, sprintf('Expected endpoint method to be %s, but it was %s', $method, $this->requestedMethod));
        return $this;
    }
}
