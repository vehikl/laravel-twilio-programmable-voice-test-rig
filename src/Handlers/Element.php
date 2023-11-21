<?php

namespace Vehikl\LaravelTwilioProgrammableVoiceTestRig\Handlers;

use Closure;
use DOMElement;
use ReflectionClass;
use Vehikl\LaravelTwilioProgrammableVoiceTestRig\CallStatus;
use Vehikl\LaravelTwilioProgrammableVoiceTestRig\ProgrammableVoiceRig;

class Element
{
    const MAP = [
        'Response' => Response::class,
        'Dial' => Dial::class,
        'Gather' => Gather::class,
        'Hangup' => Hangup::class,
        'Record' => Record::class,
        'Redirect' => Redirect::class,
        'Say' => NonActionableNoChildren::class,
        'Play' => NonActionableNoChildren::class,
        'Pause' => NonActionableNoChildren::class,
    ];

    /**
     * @param array<int,mixed> $customMapping
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

    public function hasAttr(string $name, ?string $value): bool
    {
        $attribute = $this->element->attributes->getNamedItem($name);
        if (!$attribute) return false;
        if (!$value) return true;
        return $attribute->nodeValue == $value;
    }

    public function text(): string
    {
        return $this->element->textContent;
    }

    public function callStatus(CallStatus $previous): CallStatus
    {
        return CallStatus::in_progress;
    }

    /**
     * @param Closure():ProgrammableVoiceRig $next
     */
    public function handle(Closure $next): ProgrammableVoiceRig
    {
        return $next();
    }

    public function nextElement(): ?DOMElement
    {
        return $this->element->nextElementSibling
            ?? null;
    }
}
