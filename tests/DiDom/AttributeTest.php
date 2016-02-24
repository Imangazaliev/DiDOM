<?php

namespace Tests\DiDom;

use DiDom\Attribute;
use DiDom\Element;
use Tests\TestCase;

class AttributeTest extends TestCase
{
    public function testConstructor()
    {
        $name = 'testName';
        $value = 'testValue';

        $attribute = new Attribute(new \DOMAttr($name, $value));

        $this->assertEquals($name, $attribute->name());
        $this->assertEquals($value, $attribute->text());
    }

    public function testGetUnknownOwner()
    {
        $name = 'testName';
        $value = 'testValue';

        $domAttr = new \DOMAttr($name, $value);
        $attribute = new Attribute($domAttr);

        $this->assertNull($attribute->getOwner());
    }

    public function testGetOwner()
    {
        $attributeName = 'name';
        $attributeValue = 'email';

        $node = $this->createNode('input', '', [$attributeName => $attributeValue]);

        $element = new Element($node);
        $attribute = new Attribute($node->getAttributeNode($attributeName));

        $owner = $attribute->getOwner();

        $this->assertEquals($element->__toString(), $owner->__toString());
    }
}