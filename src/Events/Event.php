<?php

namespace Vehikl\LaravelTwilioProgrammableVoiceTestRig\Events;

use ReflectionClass;
use Twilio\TwiML\Voice\Record;
use Vehikl\LaravelTwilioProgrammableVoiceTestRig\Handlers\Element;
use Vehikl\LaravelTwilioProgrammableVoiceTestRig\ProgrammableVoiceRig;

class Event
{
    const MAP = [
        'Dial' => DialEvent::class,
        'Gather' => GatherEvent::class,
        'Record' => RecordEvent::class,
    ];

    public static function fromElement(ProgrammableVoiceRig $rig, Element $element): self
    {
        $className = self::MAP[$element->element->tagName] ?? null;
        return (new ReflectionClass($className))->newInstance($rig, $element);
    }

    public function __construct(protected ProgrammableVoiceRig $rig, protected Element $element)
    {
    }
}
