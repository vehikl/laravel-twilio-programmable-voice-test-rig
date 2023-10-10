<?php

namespace Vehikl\LaravelTwilioProgrammableVoiceTestRig;

use PHPUnit\Framework\Assert;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use ReflectionClass;
use SimpleXMLElement;
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
    private ?array $nextAction = null;
    private ?string $currentUrl = null;

    private Application $app;

    private ?Request $request = null;

    /** @var array<int,string> */
    private array $statusChange;

    public function __construct(Application $application, string $direction = 'inbound', string $callStatus = 'ringing')
    {
        $this->app = $application;
        $this->twilioCallParameters = [
            'AccountSid' => sprintf('AC%s', fake()->uuid),
            'CallSid' => sprintf('CA%s', fake()->uuid),
            'CallStatus' => $callStatus,
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

    private function getTwimlHandler(string $tag): ?TwimlHandler
    {
        $classRef = $this->customTwimlHandlers[$tag] ?? self::TWIML_HANDLERS[$tag] ?? null;
        if (!$classRef) {
            return null;
        }
        return (new ReflectionClass($classRef))->newInstance();
    }


    public function from(PhoneNumber $phoneNumber): self
    {
        $this->twilioCallParameters = array_merge(
            $this->twilioCallParameters,
            $phoneNumber->toParameters('From'),
        );
        return $this;
    }

    public function to(PhoneNumber $phoneNumber): self
    {
        $this->twilioCallParameters = array_merge(
            $this->twilioCallParameters,
            $phoneNumber->toParameters('To'),
        );
        return $this;
    }

    public function forwardedFrom(PhoneNumber $phoneNumber): self
    {
        $this->twilioCallParameters = array_merge(
            $this->twilioCallParameters,
            $phoneNumber->toParameters('ForwardedFrom'),
        );
        return $this;
    }

    public function queueInput(?string $recordingUrl = null, ?int $recordingDuration = null, ?string $digits = null): self
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

    public function shiftDial(): ?array
    {
        return array_shift($this->dialQueue) ?? [
            'DialCallStatus' => 'completed',
            'DialCallSid' => 'CA' . fake()->uuid,
            'DialCalLDuration' => 0,
            'DialBridged' => false,
        ];
    }
    /**
     * @param array<int,mixed> $data
     */
    public function setNextAction(string $method, string $url, array $data = []): void
    {
        if ($this->nextAction) {
            return;
        }
        $this->nextAction = [$method, $url, $data];
    }

    /**
     * @param array<int,mixed> $extraData
     */
    private function handleRequest(string $method, string $url, array $extraData = [], bool $skipNavigation = false): Response
    {
        $body = [...$this->twilioCallParameters, ...$extraData];
        $request = Request::create($url, $method, $body);
        $response = $this->app->handle($request);
        if (!$skipNavigation) {
            $this->currentUrl = $request->url;
            $this->request = $request;
        }
        return $response;
    }


    public function statusChangeEndpoint(string $url, string $method = 'POST'): self
    {
        $this->statusChange = [$method, $url];
        return $this;
    }

    public function setCallStatus(string $callStatus): void
    {
        if ($this->twilioCallParameters['CallStatus'] === $callStatus) {
            return;
        }
        $this->twilioCallParameters['CallStatus'] = $callStatus;
        list($method, $url)  = $this->statusChange;
        $this->handleRequest($method, $url, skipNavigation: true);
    }

    public function endpoint(string $url, string $method = 'POST'): self
    {
        $this->setCallStatus('in-progress');
        $this->setNextAction($method, $url);
        return $this->next();
    }

    public function getCurrentUrl(): string
    {
        return $this->request?->fullUrl() ?? '';
    }

    public function next(): self
    {
        if (!$this->nextAction) {
            return $this;
        }
        list($method, $url, $data) = $this->nextAction;
        $this->nextAction = null;
        $this->response = $this->handleRequest($method, $url, $data);
        $this->handleTwiml($this->response);
        return $this;
    }

    private function handleTwiml(Response $response): void
    {
        $content = $response->getContent();
        $xml = simplexml_load_string($content);
        if (!$this->isTwiml($xml)) {
            return;
        }

        foreach($xml->children() as $tag) {
            $handler = $this->getTwimlHandler($tag->getName());
            if ($handler?->handle($this, $tag)) {
                return;
            }
        }

        $this->setCallStatus('completed');
    }

    private function isTwiml(SimpleXMLElement $xml): bool
    {
        return $xml->getName() === 'Response';
    }

    public function assertNextEndpoint(string $uri): self
    {
        $this->next();

        $requestUrl = strpos($uri, '?') !== false
            ? $this->request->fullUrl()
            : $this->request->url();

        Assert::assertEquals($uri, $requestUrl, "Twiml redirect to {$requestUrl} but you expected {$uri}");

        return $this;
    }

    /**
     * @param mixed $replacements
     */
    public function assertTwiml(string $formattedXML, ...$replacements): self
    {
        $normalized = sprintf(
            "%s\n<Response>%s</Response>\n",
            '<?xml version="1.0" encoding="UTF-8"?>',
            collect(explode("\n", sprintf($formattedXML, ...$replacements)))
                ->map(fn ($line) => trim($line))
                ->filter(fn ($line) => strlen($line) > 0)
                ->join(""),
        );
        $replaceAmps = str_replace('&', '&amp;', $normalized);

        Assert::assertEquals($replaceAmps, $this->response->getContent(), 'Expected twiml does not match actual');

        return $this;
    }

    public function assertCallEnded(): self
    {
        $this->next();
        $status = $this->twilioCallParameters['CallStatus'];
        Assert::assertTrue($this->isCompleted(), "Call status is $status, which is not completed or a final status");
        return $this;
    }

    public function getCallStatus(): string
    {
        return $this->twilioCallParameters['CallStatus'];
    }

    private function isCompleted(): bool
    {
        return in_array($this->twilioCallParameters['CallStatus'], [
            'completed',
            'busy',
            'no-answer',
            'canceled',
            'failed',
        ]);
    }

    public function tap(Callable $callback): self
    {
        $callback($this->request, $this->response);

        return $this;
    }

    public function getCallSid(): string
    {
        return $this->twilioCallParameters['CallSid'];
    }

    public function twiml(): string
    {
        return $this->request->getContent();
        
    }
}
