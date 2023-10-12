<?php

namespace Vehikl\LaravelTwilioProgrammableVoiceTestRig;

use PHPUnit\Framework\Assert as PHPUnitAssert;

class Assert {
    public function __construct(public ProgrammableVoiceRig $rig) {}

    /**
     * @param mixed $replacements
     */
    protected function normalizeTwiml(string $xml, ...$replacements): string
    {
        $normalized = collect(explode("\n", sprintf($xml, ...$replacements)))
            ->map(fn ($line) => trim($line))
            ->filter(fn ($line) => strlen($line) > 0)
            ->join("");

        return str_replace('&', '&amp;', $normalized);
    }

    /**
     * @param mixed $replacements
     */
    public function twiml(string $xml, ...$replacements): self
    {
        $expectedTwiml = sprintf(
            "%s\n<Response>%s</Response>\n",
            '<?xml version="1.0" encoding="UTF-8"?>',
            $this->normalizeTwiml($xml, ...$replacements),
        );

        PHPUnitAssert::assertEquals($expectedTwiml, $this->rig->twiml(), 'Expected twiml does not match actual');

        return $this;
    }
    /**
     * @param mixed $replacements
     */
    public function containsTwiml(string $xml, ...$replacements): self
    {
        $expectedPartialTwiml = $this->normalizeTwiml($xml, ...$replacements);
        
        PHPUnitAssert::assertStringContainsString($expectedPartialTwiml, $this->rig->twiml(), 'Expected twiml does not match actual');

        return $this;
    }

    public function callStatus(string $expectedCallStatus, string $message = ''): self
    {
        
        PHPUnitAssert::assertEquals($expectedCallStatus, $this->rig->getCallStatus(), $message);

        return $this;
    }

    public function endpoint(string $expectedUri, string $expectedMethod = 'POST'): self
    {
        $request = $this->rig->getRequest();
        if (!$request) {
            PHPUnitAssert::fail('No request to assert against');
            return $this;
        }
        $uri = strpos($expectedUri, '?') !== false
            ? $request->fullUrl()
            : $request->url();

        PHPUnitAssert::assertEquals($expectedUri, $uri, 'Uri does not match');
        PHPUnitAssert::assertEquals($expectedMethod, $request->method(), 'Method does not match');

        return $this;
    }









}
