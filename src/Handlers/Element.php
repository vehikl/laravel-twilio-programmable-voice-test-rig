<?php

namespace Vehikl\LaravelTwilioProgrammableVoiceTestRig\Handlers;

use DOMElement;
use ReflectionClass;
use Vehikl\LaravelTwilioProgrammableVoiceTestRig\CallStatus;
use Vehikl\LaravelTwilioProgrammableVoiceTestRig\ProgrammableVoiceRig;

class Element
{
    const MAP = [
        'Hangup' => Hangup::class,
        'Pause' => Pause::class,
        'Redirect' => Redirect::class,
        'Reject' => Reject::class,
        'Response' => Response::class,
    ];

    /**
     * @param array<int,mixed> $customMapping
     * @throws \ReflectionException
     */
    public static function fromElement(
        ProgrammableVoiceRig $rig,
        DOMElement $element,
        ?self $parent = null,
        array $customMapping = [],
    ): self
    {
        $class = $customMapping[$element->nodeName]
            ?? self::MAP[$element->nodeName]
            ?? null;

        if (!$class) {
            return new self($rig, $element, $parent);
        }

        return (new ReflectionClass($class))->newInstance($rig, $element, $parent);
    }

    public function __construct(
        public ProgrammableVoiceRig $rig,
        public DOMElement $element,
        public ?self $parent = null,
    )
    {
    }

    public function attr(string $name, ?string $fallback = null): mixed
    {
        $node = $this->element->attributes->getNamedItem($name);
        return $node?->nodeValue ?? $fallback;
    }

    public function text(): string
    {
        return $this->element->textContent;
    }

    public function callStatus(CallStatus $previous): CallStatus
    {
        return CallStatus::in_progress;
    }

    public function nextElement(): ?DOMElement
    {
        return $this->element->nextElementSibling;
    }
}
