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

    /** @var array<int,string> */
    private array $statusChange = [];

    public function __construct(protected Application $app, protected TwimlApp $twimlApp, string $direction = 'inbound', string $callStatus = 'ringing')
    {
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


    public function setCallStatus(string $callStatus): void
    {
        if ($this->twilioCallParameters['CallStatus'] === $callStatus) {
            return;
        }
        $this->twilioCallParameters['CallStatus'] = $callStatus;
        if (count($this->statusChange) === 2) {
            list($method, $url)  = $this->statusChange;
            $this->handleRequest($url, $method, skipNavigation: true);
        }
    }

    public function call(): self
    {
        $voiceApp = $this->twimlApp->voice;

        if (!$voiceApp) {
            PHPUnitAssert::fail('Unable to make a call, voice app not configured');
            return $this;
        }

        $this->setCallStatus('in-progress');

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

    public function followTwiml(): self
    {
        $alreadyHandled = false;
        $this->handleTwiml($this->response, function (string $url, string $method = 'POST', array $data = []) use (&$alreadyHandled) {
            if ($alreadyHandled) {
                throw new Exception('Attempted to follow twiml more than once on the same response');
            }

            $this->response = $this->handleRequest($url, $method, $data);
            $alreadyHandled = true;
        });
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
            var_dump($this->request->url(), (string)$e);
            return;
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


    public function getCallStatus(): string
    {
        return $this->twilioCallParameters['CallStatus'];
    }

    public function getCallSid(): string
    {
        return $this->twilioCallParameters['CallSid'];
    }

    public function twiml(): string
    {
        return $this->response->getContent();
        
    }
}
