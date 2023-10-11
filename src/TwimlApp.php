<?php

namespace Vehikl\LaravelTwilioProgrammableVoiceTestRig;

class TwimlApp
{
    public function __construct(public ?TwimlAppConfiguration $voice = null)
    {
    }
}
