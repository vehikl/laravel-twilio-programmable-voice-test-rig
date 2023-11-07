<?php

namespace Vehikl\LaravelTwilioProgrammableVoiceTestRig;

use AssertionError;
use Closure;
use DOMDocument;
use DOMElement;
use DOMNamedNodeMap;
use PHPUnit\Framework\Assert;

class AssertContext
{
    public DOMDocument|DOMElement|null $root = null;
    public DOMDocument|DOMElement|null $lastAssertedDomNode;
    protected ?string $actualXML = null;


    public function setAssertionContext(DOMDocument|DOMElement $root): self
    {
        $this->root = $root;
        $this->lastAssertedDomNode = null;
        if ($root instanceof DOMDocument) {
            $this->actualXML = $root->saveXML();
        } else {
            $this->actualXML = $root->ownerDocument->saveXML($root);
        }

        return $this;
    }

    public function getCallStatus(): CallStatus
    {
        return CallStatus::tryFrom($this->twilioCallParameters['CallStatus']);
    }

    /**
     * @param mixed $replacements
     */
    protected function normalizeTwiml(string $xml, ...$replacements): string
    {
        $normalized = collect(explode("\n", sprintf($xml, ...$replacements)))
            ->map(fn($line) => trim($line))
            ->filter(fn($line) => strlen($line) > 0)
            ->join("");

        return str_replace('&', '&amp;', $normalized);
    }

    /**
     * @param mixed $replacements
     */
    public function assertTwimlEquals(string $xml, ...$replacements): self
    {
        $expectedTwiml = sprintf(
            "%s\n<Response>%s</Response>\n",
            '<?xml version="1.0" encoding="UTF-8"?>',
            $this->normalizeTwiml($xml, ...$replacements),
        );

        Assert::assertEquals($expectedTwiml, $this->actualXML, 'Expected twiml does not match actual');

        return $this;
    }

    /**
     * @param mixed $replacements
     */
    public function assertTwimlContains(string $xml, ...$replacements): self
    {
        $expectedPartialTwiml = $this->normalizeTwiml($xml, ...$replacements);

        Assert::assertStringContainsString(
            $expectedPartialTwiml,
            $this->actualXML,
            'Expected twiml does not match actual',
        );

        return $this;
    }

    private function domAttributesToArray(DOMNamedNodeMap $attributes): array
    {
        $attrs = [];
        foreach ($attributes->getIterator() as $attribute) {
            /** @var DOMAttr $attribute */
            $attrs[$attribute->nodeName] = $attribute->nodeValue;
        }
        return $attrs;
    }

    /**
     * @param array<string,mixed> $expectedAttributes
     */
    private function allAttributesExist(array $expectedAttributes, DOMNamedNodeMap $attributes): bool
    {
        $domAttrs = $this->domAttributesToArray($attributes);
        foreach ($expectedAttributes as $key => $value) {
            $domValue = $domAttrs[$key] ?? null;
            $expectedValue = is_bool($value)
                ? ($value ? 'true' : 'false')
                : $value;
            if ($domValue != $expectedValue) {
                return false;
            }
        }
        return true;
    }

    private function findElement(string $tagName, Closure $criteria, DOMDocument|DOMElement $parent): ?DOMElement
    {
        foreach ($parent->getElementsByTagName($tagName)->getIterator() as $tag) {
            /** @var DOMElement $tag */
            if ($criteria($tag)) {
                return $tag;
            }
        }

        return null;
    }
    

    /**
     * @param array<string,mixed> $attributes
     */
    private function assertOnElement(string $tagName, array $attributes, DOMDocument|DOMElement $parent, bool $exactAttributes = false, bool $setLastAssertedDomNode = true): self
    {
        $element = $this->findElement($tagName, function (DOMElement $tag) use ($attributes, $exactAttributes) {
            if (!$this->allAttributesExist($attributes, $tag->attributes)) {
                return false;
            }
            if ($exactAttributes && count($attributes) != $tag->attributes->count()) {
                return false;
            }

            return true;
        }, $this->root);

        if ($element) {
            if ($setLastAssertedDomNode) {
                $this->lastAssertedDomNode = $element;
            }
            Assert::assertEquals($tagName, $element->nodeName);
            return $this;
        }

        Assert::fail(sprintf('Unable to find any %s tag with specified attributes', $tagName));
    }

    /**
     * @param array<string,mixed> $attributes
     */
    public function assertElement(string $tagName, array $attributes = [], bool $exactAttributes = false): self
    {
        return $this->assertOnElement($tagName, $attributes, $this->root, $exactAttributes, true);
    }

    public function assertTextContent(string $body): self
    {
        $node = $this->lastAssertedDomNode ?? $this->root;
        Assert::assertNotNull($node, 'You must make an element assertion before using assertTextContent');
        Assert::assertEquals($body, $node->textContent);
        return $this;
    }

    public function assertTextContentContains(string $body): self
    {
        Assert::assertNotNull($this->lastAssertedDomNode, 'You must make an element assertion before using assertTextContent');
        Assert::assertStringContainsString($body, $this->lastAssertedDomNode->textContent, "Unable to find '$body' in node text content:" . PHP_EOL . $this->lastAssertedDomNode->C14N());
        return $this;
    }
    /**
     * @param Closure(AssertContext $context): void $asserter
     */
    public function assertElementChildren(Closure $asserter): self
    {
        $root = $this->lastAssertedDomNode ?? $this->root;
        if (!$this->lastAssertedDomNode) {
            Assert::fail('assertElementChildren must be used after asserting on a specific element');
        }
        $asserter((new AssertContext)->setAssertionContext($this->lastAssertedDomNode));
        return $this;
    }

    /**
     * @param array<string,mixed> $attributes
     */
    public function assertElementChild(string $tagName, array $attributes, bool $exactAttributes = false): self
    {
        Assert::assertNotNull($this->lastAssertedDomNode, 'You must make an element assertion before using assertElementChild');
        return $this->assertOnElement($tagName, $attributes, $this->lastAssertedDomNode, $exactAttributes, false);
    }

    public function assertSay(string $text): self
    {
        $element = $this->findElement('Say', function (DOMElement $tag) use ($text) {
            return $tag->textContent == $text;
        }, $this->root);
        Assert::assertNotNull($element, "Unable to find a <Say>$text</Say> in:" . PHP_EOL . $this->root->C14N());
        return $this;
    }

    public function assertPlay(string $file): self
    {
        return $this->assertTwimlContains('<Play>%s</Play>', $file);
    }

    public function assertPause(int $seconds): self
    {
        return $this->assertOnElement('Pause', ['length' => $seconds], $this->root, setLastAssertedDomNode: false);
    }

    /**
     * @param array<int,mixed> $attributes
     */
    public function assertDial(string $phoneNumber, array $attributes = [], bool $exactAttributes = false): self
    {
        return $this
            ->assertElement('Dial', $attributes, $exactAttributes)
            ->assertTextContent($phoneNumber);
    }

    /**
     * @param array<string,mixed> $attributes
     */
    public function assertRedirect(string $uri, array $attributes = [], bool $exactAttributes = false): self
    {
        return $this
            ->assertElement('Redirect', $attributes, $exactAttributes)
            ->assertTextContent($uri);
    }

    /**
     * @param array<string,mixed> $attributes
     */
    public function assertGather(array $attributes = [], bool $exactAttributes = false): self
    {
        return $this->assertElement('Gather', $attributes, $exactAttributes);
    }

    /**
     * @param array<string,mixed> $attributes
     */
    public function assertRecord(array $attributes = [], bool $exactAttributes = false): self
    {
        return $this->assertElement('Record', $attributes, $exactAttributes);
    }

    public function assertCallStatus(CallStatus|string $expectedCallStatus): self
    {
        $status = is_string($expectedCallStatus)
            ? CallStatus::tryFrom($expectedCallStatus)
            : $expectedCallStatus;

        Assert::assertEquals($status, $this->getCallStatus());

        return $this;
    }

    private function assertTwimlOrderRecursive(array &$tags, DOMDocument|DOMElement|null $current = null): self
    {
        if (!$current) {
            return $this;
        }

        if ($current->nodeName == $tags[0]) {
            $tags = array_slice($tags, 1);
        }

        if (count($tags) == 0) {
            return $this;
        }

        return $this->assertTwimlOrderRecursive($tags, $current->firstElementChild)
            ->assertTwimlOrderRecursive($tags, $current->nextElementSibling);
    }
    

    /**
     * @param array<int,mixed> $tags
     */
    public function assertTwimlOrder(array $tags): self
    {
        $root = $this->root instanceof DOMDocument
            ? $this->root->firstElementChild
            : $this->root;

        $this->assertTwimlOrderRecursive($tags, $root);
        if (count($tags) > 0) {
            Assert::fail('Missing twiml elements in order: ' . implode(', ', $tags));
        }

        return $this;
    }

    public function assertHangup(): self
    {
        return $this->assertElement('Hangup');
    }
}
