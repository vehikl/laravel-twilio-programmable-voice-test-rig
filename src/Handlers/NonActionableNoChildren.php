<?php

namespace Vehikl\LaravelTwilioProgrammableVoiceTestRig\Handlers;

use DOMElement;

class NonActionableNoChildren extends Element
{
    public function nextElement(): ?DOMElement
    {
        return $this->element->nextElementSibling
            ?? null;
    }
}
