<?php

namespace Vehikl\LaravelTwilioProgrammableVoiceTestRig;

class TwimlAppConfiguration
{
    public string $sid;

    public function __construct(
        public string $requestUrl,
        public string $requestMethod = 'POST',
        public ?string $fallbackUrl = null,
        public string $fallbackMethod = 'POST',
        public ?string $statusCallbackUrl = null,
        public string $statusCallbackMethod = 'POST',
    )
    {
        $this->sid = 'AP' . fake()->uuid;
    }
}
