<?php

namespace Tests\DiDom;

use Tests\TestCase;
use DiDom\Element;
use DiDom\Document;

class ElementTest extends TestCase
{
    public function testInvalidAttributesParameter()
    {
        $this->setExpectedException('InvalidArgumentException');

        new Element('span', 'hello', null);
    }

    public function testCreate()
    {
        $attributes = ['name' => 'username', 'value' => 'John'];

        $element = new Element('input', '', $attributes);

        $this->assertEquals('input', $element->getElement()->tagName);
        $this->assertEquals('username', $element->getElement()->getAttribute('name'));
        $this->assertEquals('John', $element->getElement()->getAttribute('value'));
    }

    public function testCreateFromDomElement()
    {
        $domElement = $this->createDomElement('input');

        $element = new Element($domElement);

        $this->assertTrue($element->getElement()->isSameNode($domElement));
    }

    public function testGetElement()
    {
        $domElement = $this->createDomElement('input');
        $domElement->setAttribute('value', 'test');

        $element     = new Element($domElement);
        $domElement2 = $element->getElement();

        $this->assertTrue($domElement->isSameNode($domElement2));
    }

    public function testSetAttribute()
    {
        $domElement = $this->createDomElement('input');

        $element = new Element($domElement);
        $element->setAttribute('value', 'test');

        $domElement = $element->getElement();

        $this->assertEquals('test', $domElement->getAttribute('value'));
    }

    public function testGetAttribute()
    {
        $domElement = $this->createDomElement('input');

        $element = new Element($domElement);
        $this->assertEquals(null, $element->getAttribute('value'));
        $this->assertEquals('default', $element->getAttribute('value', 'default'));

        $domElement->setAttribute('value', 'test');

        $element = new Element($domElement);
        $this->assertEquals('test', $element->getAttribute('value'));
    }

    public function testHasAttribute()
    {
        $domElement = $this->createDomElement('input');

        $element = new Element($domElement);
        $this->assertFalse($element->hasAttribute('value'));

        $domElement->setAttribute('value', 'test');

        $element = new Element($domElement);
        $this->assertTrue($element->hasAttribute('value'));
    }

    public function testRemoveAttribute()
    {
        $domElement = $this->createDomElement('input');
        $domElement->setAttribute('value', 'test');

        $element = new Element($domElement);
        $this->assertTrue($element->hasAttribute('value'));

        $element->removeAttribute('value');
        $this->assertFalse($element->hasAttribute('value'));
    }

    public function testAttrSet()
    {
        $domElement = $this->createDomElement('input');

        $element = new Element($domElement);
        $element->attr('value', 'test');

        $this->assertEquals('test', $element->getElement()->getAttribute('value'));
    }

    public function testAttrGet()
    {
        $domElement = $this->createDomElement('input');
        $domElement->setAttribute('value', 'test');

        $element = new Element($domElement);

        $this->assertEquals('test', $element->attr('value'));
    }

    public function testGetMagicMethod()
    {
        $domElement = $this->createDomElement('input');
        $domElement->setAttribute('value', 'test');

        $element = new Element($domElement);

        $this->assertEquals('test', $element->value);
    }

    public function testSetMagicMethod()
    {
        $domElement = $this->createDomElement('input');

        $element = new Element($domElement);
        $element->value = 'test';

        $this->assertEquals('test', $element->getElement()->getAttribute('value'));
    }

    public function testIssetMagicMethod()
    {
        $domElement = $this->createDomElement('input');

        $element = new Element($domElement);
        $this->assertFalse(isset($element->value));

        $domElement->setAttribute('value', 'test');

        $element = new Element($domElement);
        $this->assertTrue(isset($element->value));
    }

    public function testUnsetMagicMethod()
    {
        $domElement = $this->createDomElement('input');
        $domElement->setAttribute('value', 'test');

        $element = new Element($domElement);
        $this->assertTrue($element->hasAttribute('value'));

        unset($element->value);
        $this->assertFalse($element->hasAttribute('value'));
    }

    public function testToDocument()
    {
        $domElement = $this->createDomElement('input');

        $element  = new Element($domElement);
        $document = $element->toDocument();

        $this->assertInstanceOf('DiDom\Document', $document);
    }

    public function testHtml()
    {
        $element = new Element('span', 'hello');
        $html = $element->html();

        $this->assertEquals('<span>hello</span>', $html);
    }

    public function testGetText()
    {
        $domElement = $this->createDomElement('span', 'hello');
        $element    = new Element($domElement);

        $this->assertEquals('hello', $element->text());
    }

    public function testSetValue()
    {
        $element = new Element('span', 'hello');

        $element->setValue('test');

        $this->assertEquals('test', $element->text());
    }

    public function testIs()
    {
        $element  = new Element('span', 'hello');
        $element2 = new Element('span', 'hello');

        $this->assertTrue($element->is($element));
        $this->assertFalse($element->is($element2));
    }

    public function testIsException()
    {
        $this->setExpectedException('InvalidArgumentException');

        $element = new Element('span', 'hello');
        $element->is(null);
    }

    public function testParent()
    {
        $html = $this->loadFixture('posts.html');

        $document = new Document($html, false);
        $element  = $document->createElement('span', 'value');

        $parent = $element->parent();

        $this->assertInstanceOf('DiDom\Document', $parent);
        $this->assertTrue($document->is($parent));
    }
}
