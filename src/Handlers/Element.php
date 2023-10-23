<?php

namespace Vehikl\LaravelTwilioProgrammableVoiceTestRig\Handlers;

use ReflectionClass;
use SimpleXMLElement;
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

    public static function fromElement(ProgrammableVoiceRig $rig, SimpleXMLElement $element, ?TwimlElement $parent = null, array $customMapping = []): self
    {
        $class = $customMapping[$element->getName()] ?? self::MAP[$element->getName()] ?? null;
        if (!$class) {
            return new self($rig, $element, $parent);
        }

        return (new ReflectionClass($class))->newInstance($rig, $element, $parent);
    }

    public function __construct(
        public ProgrammableVoiceRig $rig,
        public SimpleXMLElement $element,
        public ?TwimlElement $parent = null,
    )
    {
    }

    public function isActionable(): bool
    {
        return false;
    }

    public function runAction(Callable $nextAction): bool
    {
        return false;
    }
}
