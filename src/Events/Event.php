<?php

namespace Vehikl\LaravelTwilioProgrammableVoiceTestRig\Events;

use Vehikl\LaravelTwilioProgrammableVoiceTestRig\Handlers\Element;
use Vehikl\LaravelTwilioProgrammableVoiceTestRig\ProgrammableVoiceRig;

class Event
{
    public function __construct(protected ProgrammableVoiceRig $rig, protected Element $element)
    {
    }
}
