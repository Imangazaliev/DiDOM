<?php

namespace Tests\DiDom;

use Tests\TestCase;
use DiDom\Document;
use DiDom\Element;
use DiDom\Query;

class ElementTest extends TestCase
{
    /**
     * @expectedException InvalidArgumentException
     */
    public function testConstructorWithInvalidName()
    {
        new Element(null, 'hello');
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testConstructorWithInvalidValue()
    {
        new Element('span', []);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testConstructorWithInvalidAttributes()
    {
        new Element('span', 'hello', null);
    }

    public function testConstructor()
    {
        $element = new Element('input', null, ['name' => 'username', 'value' => 'John']);

        $this->assertEquals('input', $element->getNode()->tagName);
        $this->assertEquals('username', $element->getNode()->getAttribute('name'));
        $this->assertEquals('John', $element->getNode()->getAttribute('value'));

        // create from DOMElement
        $node = $this->createNode('input');
        $element = new Element($node);

        $this->assertEquals($node, $element->getNode());
    }

    public function testCreate()
    {
        $element = Element::create('span', 'Foo', ['class' => 'bar']);

        $this->assertEquals('span', $element->tag);
        $this->assertEquals('Foo', $element->text());
        $this->assertEquals(['class' => 'bar'], $element->attributes());
    }

    public function testCreateBySelector()
    {
        $element = Element::createBySelector('li.item.active', 'Foo', ['data-id' => 1]);

        $this->assertEquals('li', $element->tag);
        $this->assertEquals('Foo', $element->text());
        $this->assertEquals(['class' => 'item active', 'data-id' => 1], $element->attributes());
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testAppendChildWithInvalidArgument()
    {
        $element = new Element('span', 'hello');

        $element->appendChild('foo');
    }

    public function testAppendChild()
    {
        $list = new Element('ul');

        $this->assertCount(0, $list->find('li'));

        $node = new Element('li', 'foo');
        $list->appendChild($node);

        $this->assertCount(1, $list->find('li'));

        $items = [];
        $items[] = new Element('li', 'bar');
        $items[] = new Element('li', 'baz');

        $list->appendChild($items);

        $this->assertCount(3, $list->find('li'));
    }

    public function testHas()
    {
        $document = new \DOMDocument();
        $document->loadHTML('<div><span class="foo">bar</span></div>');

        $node = $document->getElementsByTagName('div')->item(0);
        $element = new Element($node);

        $this->assertTrue($element->has('.foo'));
        $this->assertFalse($element->has('.bar'));
    }

    /**
     * @dataProvider findTests
     */
    public function testFind($html, $selector, $type, $count)
    {
        $document = new \DOMDocument();
        $document->loadHTML($html);

        $node = $document->getElementsByTagName('body')->item(0);
        $element = new Element($node);

        $elements = $element->find($selector, $type);

        $this->assertTrue(is_array($elements));
        $this->assertEquals($count, count($elements));

        foreach ($elements as $element) {
            $this->assertInstanceOf('DiDom\Element', $element);
        }
    }

    public function testFirst()
    {
        $html = '<ul><li>One</li><li>Two</li><li>Three</li></ul>';

        $document = new Document($html, false);

        $list = $document->first('ul');

        $item = $list->getNode()->childNodes->item(0);

        $this->assertEquals($item, $list->first('li')->getNode());

        $list = new Element('ul');

        $this->assertNull($list->first('li'));
    }

    /**
     * @dataProvider findTests
     */
    public function testFindAndReturnDomElement($html, $selector, $type, $count)
    {
        $document = new \DOMDocument();
        $document->loadHTML($html);

        $node = $document->getElementsByTagName('body')->item(0);
        $element = new Element($node);

        $elements = $element->find($selector, $type, false);

        $this->assertTrue(is_array($elements));
        $this->assertEquals($count, count($elements));

        foreach ($elements as $element) {
            $this->assertInstanceOf('DOMElement', $element);
        }
    }

    public function findTests()
    {
        $html = $this->loadFixture('posts.html');

        return array(
            array($html, '.post h2', Query::TYPE_CSS, 3),
            array($html, '.fake h2', Query::TYPE_CSS, 0),
            array($html, '.post h2, .post p', Query::TYPE_CSS, 6),
            array($html, "//*[contains(concat(' ', normalize-space(@class), ' '), ' post ')]", Query::TYPE_XPATH, 3),
        );
    }

    public function testXpath()
    {
        $html = $this->loadFixture('posts.html');

        $document = new \DOMDocument();
        $document->loadHTML($html);

        $node = $document->getElementsByTagName('body')->item(0);
        $element = new Element($node);

        $elements = $element->xpath("//*[contains(concat(' ', normalize-space(@class), ' '), ' post ')]");

        $this->assertTrue(is_array($elements));
        $this->assertEquals(3, count($elements));

        foreach ($elements as $element) {
            $this->assertInstanceOf('DiDom\Element', $element);
        }
    }

    public function testCount()
    {
        $html = '<ul><li>One</li><li>Two</li><li>Three</li></ul>';

        $document = new Document($html, false);
        $list = $document->first('ul');

        $this->assertEquals(3, $list->count('li'));

        $document = new Element('ul');

        $this->assertEquals(0, $document->count('li'));
    }

    /**
     * @expectedException RuntimeException
     */
    public function testMatchesStrictWithoutTagName()
    {
        $element = new Element('ul', null, ['id' => 'foo', 'class' => 'bar baz']);

        $element->matches('#foo.bar.baz', true);
    }

    public function testMatches()
    {
        $element = new Element('ul', null, ['id' => 'foo', 'class' => 'bar baz']);

        $this->assertTrue($element->matches('ul'));
        $this->assertTrue($element->matches('#foo'));
        $this->assertTrue($element->matches('.bar'));
        $this->assertTrue($element->matches('ul#foo.bar.baz'));
        $this->assertFalse($element->matches('a#foo.bar.baz'));

        // strict
        $this->assertTrue($element->matches('ul#foo.bar.baz', true));
        $this->assertFalse($element->matches('ul#foo.bar', true));
        $this->assertFalse($element->matches('ul#foo', true));
        $this->assertFalse($element->matches('ul.bar.baz', true));
        $this->assertFalse($element->matches('ul.bar.baz', true));

        $element = new Element('p');

        $this->assertTrue($element->matches('p', true));
    }

    public function testHasAttribute()
    {
        $node = $this->createNode('input');
        $element = new Element($node);

        $this->assertFalse($element->hasAttribute('value'));

        $node->setAttribute('value', 'test');
        $element = new Element($node);

        $this->assertTrue($element->hasAttribute('value'));
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testSetAttributeWithInvalidValue()
    {
        $element = new Element('input');
        $element->setAttribute('value', []);
    }

    public function testSetAttribute()
    {
        $node = $this->createNode('input');

        $element = new Element($node);

        $element->setAttribute('value', 'test');
        $this->assertEquals('test', $element->getAttribute('value'));

        $element->setAttribute('value', 10);
        $this->assertEquals('10', $element->getAttribute('value'));

        $element->setAttribute('value', 3.14);
        $this->assertEquals('3.14', $element->getAttribute('value'));
    }

    public function testGetAttribute()
    {
        $node = $this->createNode('input');

        $element = new Element($node);

        $this->assertEquals(null, $element->getAttribute('value'));
        $this->assertEquals('default', $element->getAttribute('value', 'default'));

        $node->setAttribute('value', 'test');

        $element = new Element($node);

        $this->assertEquals('test', $element->getAttribute('value'));
    }

    public function testRemoveAttribute()
    {
        $element = new Element('input', null, ['name' => 'username']);

        $this->assertTrue($element->hasAttribute('name'));

        $element->removeAttribute('name');

        $this->assertFalse($element->hasAttribute('name'));
    }

    public function testAttrSet()
    {
        $element = new Element('input');

        $element->attr('name', 'username');

        $this->assertEquals('username', $element->getNode()->getAttribute('name'));
    }

    public function testAttrGet()
    {
        $element = new Element('input', null, ['name' => 'username']);

        $this->assertEquals('username', $element->attr('name'));
    }

    public function testAttributes()
    {
        $attributes = ['type' => 'text', 'name' => 'username'];

        $element = new Element('input', null, $attributes);

        $this->assertEquals($attributes, $element->attributes());
    }

    public function testHtml()
    {
        $element = new Element('span', 'hello');

        $this->assertEquals('<span>hello</span>', $element->html());
    }

    public function testInnerHtml()
    {
        $innerHtml = ' Plain text <span>Lorem ipsum.</span><span>Lorem ipsum.</span>';
        $html = "<div id=\"root\">$innerHtml</div>";

        $document = new Document($html, false);

        $this->assertEquals($innerHtml, $document->first('#root')->innerHtml());
    }

    public function testSetInnerHtml()
    {
        $list = new Element('ul');

        $html = '<li>One</li><li>Two</li><li>Three</li>';

        $this->assertEquals($list, $list->setInnerHtml($html));
        $this->assertEquals(['One', 'Two', 'Three'], $list->find('li::text'));

        $html = '<div id="root"></div>';
        $innerHtml = ' Plain text <span>Lorem ipsum.</span><span>Lorem ipsum.</span>';

        $document = new Document($html, false);

        $document->first('#root')->setInnerHtml($innerHtml);

        $this->assertEquals($innerHtml, $document->first('#root')->innerHtml());
    }

    public function testHtmlWithOptions()
    {
        $html = '<html><body><span></span></body></html>';

        $document = new Document();
        $document->loadHtml($html);

        $element = $document->find('span')[0];

        $this->assertEquals('<span></span>', $element->html());
        $this->assertEquals('<span/>', $element->html(0));
    }

    public function testXml()
    {
        $element = new Element('span', 'hello');

        $prolog = '<?xml version="1.0" encoding="UTF-8"?>'."\n";

        $this->assertEquals($prolog.'<span>hello</span>', $element->xml());
    }

    public function testXmlWithOptions()
    {
        $html = '<html><body><span></span></body></html>';

        $document = new Document();
        $document->loadHtml($html);

        $element = $document->find('span')[0];

        $prolog = '<?xml version="1.0" encoding="UTF-8"?>'."\n";

        $this->assertEquals($prolog.'<span/>', $element->xml());
        $this->assertEquals($prolog.'<span></span>', $element->xml(LIBXML_NOEMPTYTAG));
    }

    public function testGetText()
    {
        $element = new Element('span', 'hello');

        $this->assertEquals('hello', $element->text());
    }

    public function testSetValue()
    {
        $element = new Element('span', 'hello');
        $element->setValue('test');

        $this->assertEquals('test', $element->text());
    }

    public function testIsTextNode()
    {
        $element = new Element('div');

        $element->setInnerHtml(' Foo <span>Bar</span>');

        $children = $element->children();

        $this->assertTrue($children[0]->isTextNode());
        $this->assertFalse($children[1]->isTextNode());
    }

    public function testIs()
    {
        $element  = new Element('span', 'hello');
        $element2 = new Element('span', 'hello');

        $this->assertTrue($element->is($element));
        $this->assertFalse($element->is($element2));
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testIsWithInvalidArgument()
    {
        $element = new Element('span', 'hello');
        $element->is(null);
    }

    public function testParent()
    {
        $html = $this->loadFixture('posts.html');
        $document = new Document($html, false);
        $element = $document->createElement('span', 'value');

        $this->assertEquals($document->getDocument(), $element->getDocument()->getDocument());
    }

    public function testClosest()
    {
        $html = '
            <nav>
                <ul class="menu">
                    <li><a href="#">Foo</a></li>
                    <li><a href="#">Bar</a></li>
                    <li><a href="#">Baz</a></li>
                </ul>
            </nav>
        ';

        $document = new Document($html);

        $menu = $document->first('.menu');
        $link = $document->first('a');

        $this->assertNull($link->closest('.unknown-class'));
        $this->assertEquals($menu, $link->closest('.menu'));
    }

    public function testPreviousSibling()
    {
        $html = '<ul><li>One</li><li>Two</li><li>Three</li></ul>';

        $document = new Document($html, false);

        $list = $document->first('ul');

        $item = $list->getNode()->childNodes->item(1);
        $item = new Element($item);

        $previousSibling = $list->getNode()->childNodes->item(0);

        $this->assertEquals($previousSibling, $item->previousSibling()->getNode());

        $item = $list->getNode()->childNodes->item(0);
        $item = new Element($item);

        $this->assertNull($item->previousSibling());

        // with text nodes
        $html = '<p>Foo <span>Bar</span> Baz</p>';

        $document = new Document($html, false);

        $paragraph = $document->first('p');
        $span = $paragraph->first('span');

        $previousSibling = $span->getNode()->previousSibling;

        $this->assertEquals($previousSibling, $span->previousSibling()->getNode());
    }

    public function testNextSibling()
    {
        $html = '<ul><li>One</li><li>Two</li><li>Three</li></ul>';

        $document = new Document($html, false);

        $list = $document->first('ul');

        $item = $list->getNode()->childNodes->item(0);
        $item = new Element($item);

        $nextSibling = $list->getNode()->childNodes->item(1);

        $this->assertEquals($nextSibling, $item->nextSibling()->getNode());

        $item = $list->getNode()->childNodes->item(2);
        $item = new Element($item);

        $this->assertNull($item->nextSibling());

        // with text nodes
        $html = '<p>Foo <span>Bar</span> Baz</p>';

        $document = new Document($html, false);

        $paragraph = $document->first('p');
        $span = $paragraph->first('span');

        $nextSibling = $span->getNode()->nextSibling;

        $this->assertEquals($nextSibling, $span->nextSibling()->getNode());
    }

    public function testChild()
    {
        $html = '<ul><li>One</li><li>Two</li><li>Three</li></ul>';

        $document = new Document($html, false);

        $list = $document->first('ul');

        $this->assertEquals($list->getNode()->childNodes->item(0), $list->child(0)->getNode());
        $this->assertEquals($list->getNode()->childNodes->item(2), $list->child(2)->getNode());
        $this->assertNull($list->child(3));

        // with text nodes
        $html = '<p>Foo <span>Bar</span> Baz</p>';

        $document = new Document($html, false);

        $paragraph = $document->first('p');

        $child = $paragraph->getNode()->childNodes->item(0);

        $this->assertEquals($child, $paragraph->child(0)->getNode());
    }

    public function testFirstChild()
    {
        $html = '<ul><li>One</li><li>Two</li><li>Three</li></ul>';

        $document = new Document($html, false);

        $list = $document->first('ul');

        $this->assertEquals($list->getNode()->firstChild, $list->firstChild()->getNode());

        $list = new Element('ul');

        $this->assertNull($list->firstChild());

        // with text nodes
        $html = '<p>Foo <span>Bar</span> Baz</p>';

        $document = new Document($html, false);

        $paragraph = $document->first('p');

        $firstChild = $paragraph->getNode()->firstChild;

        $this->assertEquals($firstChild, $paragraph->firstChild()->getNode());
    }

    public function testLastChild()
    {
        $html = '<ul><li>One</li><li>Two</li><li>Three</li></ul>';

        $document = new Document($html, false);
        $list = $document->first('ul');

        $this->assertEquals($list->getNode()->lastChild, $list->lastChild()->getNode());

        $list = new Element('ul');

        $this->assertNull($list->lastChild());

        // with text nodes
        $html = '<p>Foo <span>Bar</span> Baz</p>';

        $document = new Document($html, false);
        $paragraph = $document->first('p');

        $lastChild = $paragraph->getNode()->lastChild;

        $this->assertEquals($lastChild, $paragraph->lastChild()->getNode());
    }

    public function testChildren()
    {
        $html = '<ul><li>One</li><li>Two</li><li>Three</li></ul>';

        $document = new Document($html, false);

        $list = $document->first('ul');

        $children = $list->children();

        foreach ($list->getNode()->childNodes as $index => $node) {
            $this->assertEquals($node, $children[$index]->getNode());
        }

        // with text nodes
        $html = '<p>Foo <span>Bar</span> Baz</p>';

        $document = new Document($html, false);

        $paragraph = $document->first('p');

        $children = $paragraph->children();

        foreach ($paragraph->getNode()->childNodes as $index => $node) {
            $this->assertEquals($node, $children[$index]->getNode());
        }
    }

    public function testParentWithoutOwner()
    {
        $element = new Element(new \DOMElement('span', 'hello'));

        $this->assertNull($element->parent());
    }

    public function testRemove()
    {
        $html = '<div><span>Foo</span></div>';
        $document = new Document($html, false);

        $element = $document->find('span')[0];

        $this->assertEquals($element->getNode(), $element->remove()->getNode());
        $this->assertCount(0, $document->find('span'));
    }

    public function testReplace()
    {
        $html = '<ul><li>One</li><li>Two</li><li>Three</li></ul>';

        $document = new Document($html, false);

        $first = $document->find('li')[0];
        $third = $document->find('li')[2];

        $this->assertEquals($first->getNode(), $first->replace($third)->getNode());
        $this->assertEquals($third->getNode(), $document->find('li')[0]->getNode());
        $this->assertCount(3, $document->find('li'));

        $document = new Document($html, false);

        $first = $document->find('li')[0];
        $third = $document->find('li')[2];

        $this->assertEquals($first->getNode(), $first->replace($third, false)->getNode());
        $this->assertEquals($third->getNode(), $document->find('li')[0]->getNode());
        $this->assertCount(2, $document->find('li'));
    }

    public function testReplaceToNewElement()
    {
        $html = '<ul><li>One</li><li>Two</li><li>Three</li></ul>';

        $document = new Document($html, false);

        $first = $document->find('li')[0];

        $newElement = new Element('li', 'Foo');

        $this->assertEquals($first->getNode(), $first->replace($newElement)->getNode());
        $this->assertEquals('Foo', $document->find('li')[0]->text());
        $this->assertCount(3, $document->find('li'));
    }

    public function testReplaceWithDifferentDocuments()
    {
        $html = '<ul><li>One</li><li>Two</li><li>Three</li></ul>';

        $document = new Document($html, false);
        $document2 = new Document($html, false);

        $first = $document->find('li')[0];
        $third = $document2->find('li')[2];

        $first->replace($third);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testReplaceWithInvalidArgument()
    {
        $html = '<ul><li>One</li><li>Two</li><li>Three</li></ul>';

        $document = new Document($html, false);

        $document->find('li')[0]->replace(null);
    }

    public function testCloneNode()
    {
        $element = new Element('input');

        $cloned = $element->cloneNode(true);

        $this->assertFalse($element->is($cloned));
    }

    public function testGetNode()
    {
        $node = $this->createNode('input');
        $element = new Element($node);

        $this->assertEquals($node, $element->getNode());
    }

    public function testGetDocument()
    {
        $html = $this->loadFixture('posts.html');

        $document = new Document($html, false);
        $element = $document->createElement('span', 'value');

        $this->assertEquals($document->getDocument(), $element->getDocument()->getDocument());
    }

    public function testToDocument()
    {
        $element = new Element('input');

        $document = $element->toDocument();

        $this->assertInstanceOf('DiDom\Document', $document);
        $this->assertEquals('UTF-8', $document->getDocument()->encoding);

        $document = $element->toDocument('CP1251');

        $this->assertEquals('CP1251', $document->getDocument()->encoding);
    }

    public function testSetMagicMethod()
    {
        $node = $this->createNode('input');

        $element = new Element($node);
        $element->name = 'username';

        $this->assertEquals('username', $element->getNode()->getAttribute('name'));
    }

    public function testGetMagicMethod()
    {
        $element = new Element('input', null, ['name' => 'username']);

        $this->assertEquals('username', $element->name);
    }

    public function testIssetMagicMethod()
    {
        $node = $this->createNode('input');
        $element = new Element($node);

        $this->assertFalse(isset($element->value));

        $node->setAttribute('value', 'test');
        $element = new Element($node);

        $this->assertTrue(isset($element->value));
    }

    public function testUnsetMagicMethod()
    {
        $element = new Element('input', null, ['name' => 'username']);

        $this->assertTrue($element->hasAttribute('name'));

        unset($element->name);

        $this->assertFalse($element->hasAttribute('name'));
    }

    public function testToString()
    {
        $element = new Element('span', 'hello');

        $this->assertEquals($element->html(), $element->__toString());
    }

    /**
     * @dataProvider findTests
     */
    public function testInvoke($html, $selector, $type, $count)
    {
        $document = new \DOMDocument();
        $document->loadHTML($html);

        $node = $document->getElementsByTagName('body')->item(0);
        $element = new Element($node);

        $elements = $element($selector, $type);

        $this->assertTrue(is_array($elements));
        $this->assertEquals($count, count($elements));

        foreach ($elements as $element) {
            $this->assertInstanceOf('DiDom\Element', $element);
        }
    }

    public function testFirstElementMethods()
    {
        $html = '<div id="first"><span><a>text</a></span></div>';
        
        $document = new Document();
        $document->loadHtml($html);
        
        $element = $document->first('#first');

        $this->assertEquals('text', $element->firstElementText('a'));
        $this->assertEquals(null, $element->firstElementText('#inexistence'));
        
        $this->assertEquals('<a>text</a>', $element->firstElementInnerHtml('span'));
        $this->assertEquals(null, $element->firstElementInnerHtml('#inexistence'));
        
        $this->assertEquals('<span><a>text</a></span>', $element->firstElementHtml('span'));
        $this->assertEquals(null, $element->firstElementHtml('#inexistence'));
        
    }
}
