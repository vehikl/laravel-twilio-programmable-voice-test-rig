<?php

namespace Vehikl\LaravelTwilioProgrammableVoiceTestRig\Handlers;

use Closure;
use DOMNode;
use ReflectionClass;
use Vehikl\LaravelTwilioProgrammableVoiceTestRig\ProgrammableVoiceRig;

class Element
{
    const MAP = [
        'Dial' => Dial::class,
        'Gather' => Gather::class,
        'Hangup' => Hangup::class,
        'Record' => Record::class,
        'Redirect' => Redirect::class,
    ];

    public static function fromElement(ProgrammableVoiceRig $rig, DOMNode $element, ?self $parent = null, array $customMapping = []): self
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
        public DOMNode $element,
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

    public function isActionable(): bool
    {
        return false;
    }

    /**
     * @param Closure(string $tag, string $url, string $method = 'POST', array $data = []): void $nextAction
     */
    public function runAction(Closure $nextAction): bool
    {
        return false;
    }
}
