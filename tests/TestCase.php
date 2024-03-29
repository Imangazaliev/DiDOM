<?php

declare(strict_types=1);

namespace DiDom\Tests;

use DOMElement;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use DOMDocument;
use Exception;

class TestCase extends PHPUnitTestCase
{
    protected function loadFixture($filename)
    {
        $path = __DIR__.'/fixtures/'.$filename;

        if (file_exists($path)) {
            return file_get_contents($path);
        }

        throw new Exception(sprintf('Fixture "%s" does not exist', $filename));
    }

    protected function createDomElement(string $tagName, string $value = '', array $attributes = []): DOMElement
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $node = $document->createElement($tagName, $value);

        foreach ($attributes as $attrName => $attrValue) {
            $node->setAttribute($attrName, $attrValue);
        }

        return $node;
    }
}
