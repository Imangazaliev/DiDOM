<?php

namespace Tests\DiDom;

use Tests\TestCase;
use DiDom\Element;
use DOMDocument;
use DOMElement;

class ElementTest extends TestCase
{
    public function testConstructor()
    {
        $domElement = $this->createDomElement('input');

        $element = new Element($domElement);
        $element = new Element('input', 'value');
    }

    public function testGetElement()
    {
        $domElement = $this->createDomElement('input');
        $domElement->setAttribute("value", "test");

        $element = new Element($domElement);

        $domElement = $element->getElement();

        $this->assertInstanceOf('DOMElement', $domElement);
        $this->assertEquals("test", $domElement->getAttribute("value"));
    }

    public function testSetAttribute()
    {
        $domElement = $this->createDomElement('input');

        $element = new Element($domElement);
        $element->setAttribute("value", "test");

        $domElement = $element->getElement();

        $this->assertEquals("test", $domElement->getAttribute("value"));
    }

    public function testGetAttribute()
    {
        $domElement = $this->createDomElement('input');

        $element = new Element($domElement);
        $this->assertEquals("default", $element->getAttribute("value", "default"));

        $domElement->setAttribute("value", "test");

        $element = new Element($domElement);
        $this->assertEquals("test", $element->getAttribute("value"));
    }

    public function testHasAttribute()
    {
        $domElement = $this->createDomElement('input');

        $element = new Element($domElement);
        $this->assertFalse($element->hasAttribute("value"));

        $domElement->setAttribute("value", "test");

        $element = new Element($domElement);
        $this->assertTrue($element->hasAttribute("value"));
    }

    public function testRemoveAttribute()
    {
        $domElement = $this->createDomElement('input');
        $domElement->setAttribute("value", "test");

        $element = new Element($domElement);
        $this->assertTrue($element->hasAttribute("value"));

        $element->removeAttribute("value");
        $this->assertFalse($element->hasAttribute("value"));        
    }

    public function testAttrSet()
    {
        $domElement = $this->createDomElement('input');
        
        $element = new Element($domElement);
        $element->attr("value", "test");

        $domElement = $element->getElement();

        $this->assertEquals("test", $domElement->getAttribute("value"));
    }

    public function testAttrGet()
    {
        $domElement = $this->createDomElement('input');
        $domElement->setAttribute("value", "test");

        $element = new Element($domElement);

        $this->assertEquals("test", $element->attr("value"));
    }

    public function testGetMagicMethod()
    {
        $domElement = $this->createDomElement('input');
        $domElement->setAttribute("value", "test");

        $element = new Element($domElement);

        $this->assertEquals("test", $element->value);
    }

    public function testSetMagicMethod()
    {
        $domElement = $this->createDomElement('input');
        
        $element = new Element($domElement);
        $element->value = "test";

        $domElement = $element->getElement();

        $this->assertEquals("test", $domElement->getAttribute("value"));
    }

    public function testIssetMagicMethod()
    {
        $domElement = $this->createDomElement('input');
        
        $element = new Element($domElement);
        $this->assertFalse(isset($element->value));

        $domElement->setAttribute("value", "test");

        $element = new Element($domElement);
        $this->assertTrue(isset($element->value));
    }

    public function testUnsetMagicMethod()
    {
        $domElement = $this->createDomElement('input');
        $domElement->setAttribute("value", "test");

        $element = new Element($domElement);
        $this->assertTrue($element->hasAttribute("value"));

        unset($element->value);
        $this->assertFalse($element->hasAttribute("value"));        
    }

    public function testToDocument()
    {
        $domElement = $this->createDomElement('input');
        
        $element = new Element($domElement);

        $document = $element->toDocument();

        $this->assertInstanceOf('DiDom\Document', $document);
    }

    public function testHtml()
    {
        $domElement = $this->createDomElement('input');
        
        $element = new Element($domElement);

        $this->assertTrue(is_string($element->html()));
    }

    public function testText()
    {
        $domElement = $this->createDomElement('input');
        
        $element = new Element($domElement);

        $this->assertTrue(is_string($element->text()));
    }

    public function createDomElement($name)
    {
        $doc = new DOMDocument("1.0");
        $domElement = $doc->createElement($name);

        return $domElement;
    }
}
