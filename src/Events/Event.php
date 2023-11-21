<?php

namespace Vehikl\LaravelTwilioProgrammableVoiceTestRig\Events;

use Vehikl\LaravelTwilioProgrammableVoiceTestRig\Handlers\Element;
use Vehikl\LaravelTwilioProgrammableVoiceTestRig\ProgrammableVoiceRig;

class Event
{
    protected array $attributes = [];

    public function __construct(protected ProgrammableVoiceRig $rig, protected Element $element)
    {
    }

    /**
     * @param array<string,mixed> $attributes
     */
    protected function with(array $attributes): ProgrammableVoiceRig
    {
        foreach ($attributes as $key => $value) {
            $this->attributes[$key] = is_bool($value)
                ? ($value ? 'true' : 'false')
                : $value;
        }

        return $this->rig;
    }
}
