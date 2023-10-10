<?php

namespace Vehikl\LaravelTwilioProgrammableVoiceTestRig;

class PhoneNumber
{
    const WHICH_TO = 'To';
    const WHICH_FROM = 'From';
    const WHICH_FOWARDED_FROM = 'ForwardedFrom';

    private string $normalized;
    private array $twilioAttrs;

    public function __construct(string $phoneNumber, ?string $city = null, ?string $state = null, ?string $zip = null, ?string $country = null)
    {
        $this->normalized = preg_replace('/^1+/', '1', preg_replace('/\D/', '', $phoneNumber));
        $this->twilioAttrs = [
            'City' => $city ?? 'New York',
            'State' => $state ?? 'New York',
            'Zip' => $zip ?? '1000',
            'Country' => $country ?? 'US',
        ];
    }

    public function formatted(): string
    {
        return strlen($this->normalized) === 10
            ? "+{$this->normalized}"
            : $this->normalized;
    }

    /**
     * @return array<string,string>
     */
    public function toParameters(string $which): array
    {
        $parameters = [
            "{$which}" => $this->formatted(),
        ];
        foreach ($this->twilioAttrs as $key => $value) {
            $parameters["{$which}{$key}"] = $value;
        }

        return $parameters;
    }
}
