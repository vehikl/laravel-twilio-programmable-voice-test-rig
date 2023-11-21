<?php

namespace Vehikl\LaravelTwilioProgrammableVoiceTestRig\Events;

use Closure;
use PHPUnit\Framework\Assert;
use Vehikl\LaravelTwilioProgrammableVoiceTestRig\ProgrammableVoiceRig;

class GatherEvent extends Event
{
    protected function getInput(): string
    {
        return $this->element->attr('input', 'dtmf');
    }
    /**
     * @param array<string,mixed> $parameters
     */
    protected function navigate(array $parameters): ProgrammableVoiceRig
    {
        return $this->rig->navigate(
            $this->element->attr('action', $this->rig->requestedUri),
            $this->element->attr('method', 'POST'),
            $parameters,
        );
    }

    /**
     * @param Closure(ProgrammableVoiceRig):void $asserter
     */
    public function assertChildren(Closure $asserter): self
    {
        $this->rig->assertElementChildren($asserter);
        return $this;
    }

    public function withSpeech(string $speechResult, float $confidence = 0.9, ?string $digits = null): ProgrammableVoiceRig
    {
        Assert::assertStringContainsString(
            'speech',
            $this->getInput(),
            sprintf('Gather expected %s, but you gave it a speech', $this->getInput()),
        );

        $digitAttributes = $digits
            ? ['Digits' => $digits]
            : [];

        return $this->navigate([
            'SpeechResult' => $speechResult,
            'Confidence' => $confidence,
            ...$digitAttributes,
        ]);
    }

    public function withDtmf(string $digits): ProgrammableVoiceRig
    {
        Assert::assertStringContainsString(
            'dtmf',
            $this->getInput(),
            sprintf('Gather expected %s, but you gave it a dtmf', $this->getInput()),
        );

        return $this->navigate(['Digits' => $digits]);
    }

    public function withDigits(string $digits): ProgrammableVoiceRig
    {
        return $this->withDtmf($digits);
    }

    public function withSilence(): ProgrammableVoiceRig
    {
        if ($this->element->attr('actionOnEmptyResult') == 'false') {
            return $this->rig;
        }

        return $this->withDtmf('');
    }

    public function withNothing(): ProgrammableVoiceRig
    {
        return $this->withSilence();
    }
}
