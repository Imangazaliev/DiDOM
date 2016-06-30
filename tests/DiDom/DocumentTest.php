<?php

namespace Tests\DiDom;

use Tests\TestCase;
use DiDom\Document;
use DiDom\Query;

class DocumentTest extends TestCase
{
    /**
     * @expectedException InvalidArgumentException
     */
    public function testConstructWithInvalidArgument()
    {
        $document = new Document(array('foo'));
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testConstructWithInvalidEncoding()
    {
        $document = new Document(array('foo'));
    }

    /**
     * @expectedException RuntimeException
     */
    public function testConstructWithNotExistingFile()
    {
        $document = new Document('path/to/file', true);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testConstructorWithInvalidArgumentType()
    {
        $document = new Document('foo', false, 'UTF-8', null);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testConstructorWithInvalidDocumentType()
    {
        $document = new Document('foo', false, 'UTF-8', 'bar');
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testLoadWithInvalidContentArgument()
    {
        $document = new Document();
        $document->load(null);
    }

    /**
     * @expectedException RuntimeException
     */
    public function testLoadWithNotExistingFile()
    {
        $document = new Document();
        $document->load('path/to/file', true);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testLoadWithInvalidDocumentTypeArgument()
    {
        $document = new Document();
        $document->load('foo', false, null);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testLoadWithInvalidDocumentType()
    {
        $document = new Document();
        $document->load('foo', false, 'bar');
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testLoadWithInvalidOptionsType()
    {
        $document = new Document();
        $document->load('foo', false, 'html', 'bar');
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testLoadHtmlWithInvalidArgument()
    {
        $document = new Document();
        $document->loadHtml(null);
    }

    /**
     * @expectedException RuntimeException
     */
    public function testLoadHtmlFileWithNotExistingFile()
    {
        $document = new Document();
        $document->loadHtmlFile('path/to/file');
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testLoadHtmlFileWithInvalidArgument()
    {
        $document = new Document();
        $document->loadHtmlFile(array('foo'));
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testLoadXmlWithInvalidArgument()
    {
        $document = new Document();
        $document->loadXml(null);
    }

    /**
     * @expectedException RuntimeException
     */
    public function testLoadXmlFileWithNotExistingFile()
    {
        $document = new Document();
        $document->loadXmlFile('path/to/file');
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testLoadXmlFileWithInvalidArgument()
    {
        $document = new Document();
        $document->loadXmlFile(array('foo'));
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testAppendChildWithInvalidArgument()
    {
        $html = $this->loadFixture('posts.html');

        $document = new Document($html);
        $document->appendChild('foo');
    }

    public function testAppendChild()
    {
        $html = '<!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <title>Document</title>
        </head>
        <body>
            
        </body>
        </html>';

        $document = new Document($html);

        $this->assertCount(0, $document->find('span'));

        $node = $document->createElement('span');
        $document->appendChild($node);

        $this->assertCount(1, $document->find('span'));

        $nodes = [];
        $nodes[] = $document->createElement('span');
        $nodes[] = $document->createElement('span');

        $document->appendChild($nodes);

        $this->assertCount(3, $document->find('span'));
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testIsWithInvalidArgument()
    {
        $document = new Document();
        $document->is(null);
    }

    /**
     * @dataProvider loadHtmlCharsetTests
     */
    public function testLoadHtmlCharset($html, $text)
    {
        $document = new Document($html, false, 'UTF-8');

        $this->assertEquals($text, $document->find('div')[0]->text());
    }

    public function testCreateElement()
    {
        $html = $this->loadFixture('posts.html');

        $document = new Document($html, false);
        $element  = $document->createElement('span', 'value');

        $this->assertInstanceOf('DiDom\Element', $element);
        $this->assertEquals('span', $element->getNode()->tagName);
        $this->assertEquals('value', $element->getNode()->textContent);

        $element = $document->createElement('span');
        $this->assertEquals('', $element->text());

        $element = $document->createElement('input', '', ['name' => 'username']);
        $this->assertEquals('username', $element->getNode()->getAttribute('name'));
    }

    public function loadHtmlCharsetTests()
    {
        return array(
            array('<html><div class="foo">English language</html>', 'English language'),
            array('<html><div class="foo">Русский язык</html>', 'Русский язык'),
            array('<html><div class="foo">اللغة العربية</html>', 'اللغة العربية'),
            array('<html><div class="foo">漢語</html>', '漢語'),
            array('<html><div class="foo">Tiếng Việt</html>', 'Tiếng Việt'),
        );
    }

    public function testHas()
    {
        $html = $this->loadFixture('posts.html');
        $document = new Document($html, false);

        $this->assertTrue($document->has('.posts'));
        $this->assertFalse($document->has('.fake'));
    }

    /**
     * @dataProvider findTests
     */
    public function testFind($html, $selector, $type, $count)
    {
        $document = new Document($html, false);
        $elements = $document->find($selector, $type);

        $this->assertTrue(is_array($elements));
        $this->assertEquals($count, count($elements));

        foreach ($elements as $element) {
            $this->assertInstanceOf('DiDom\Element', $element);
        }
    }

    /**
     * @dataProvider findTests
     */
    public function testFindAndReturnDomElement($html, $selector, $type, $count)
    {
        $document = new Document($html, false);
        $elements = $document->find($selector, $type, false);

        $this->assertTrue(is_array($elements));
        $this->assertEquals($count, count($elements));

        foreach ($elements as $element) {
            $this->assertInstanceOf('DOMElement', $element);
        }
    }

    public function testFindText()
    {
        $html = $this->loadFixture('menu.html');

        $document = new Document($html, false);
        $texts = $document->find('//a/text()', Query::TYPE_XPATH);

        $this->assertTrue(is_array($texts));
        $this->assertEquals(3, count($texts));

        $this->assertEquals(['Link 1', 'Link 2', 'Link 3'], $texts);
    }

    public function testFindAttribute()
    {
        $html = $this->loadFixture('menu.html');

        $document = new Document($html, false);
        $links = $document->find('//a/@href', Query::TYPE_XPATH);

        $this->assertTrue(is_array($links));
        $this->assertEquals(3, count($links));

        foreach ($links as $link) {
            $this->assertEquals('http://example.com', $link);
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

    public function testFirst()
    {
        $html = '<ul><li>One</li><li>Two</li><li>Three</li></ul>';

        $document = new Document($html, false);

        $items = $document->find('ul > li');

        $this->assertEquals($items[0]->getNode(), $document->first('ul > li')->getNode());

        $document = new Document();

        $this->assertNull($document->first('ul > li'));
    }

    public function testXpath()
    {
        $html = $this->loadFixture('posts.html');

        $document = new Document($html, false);
        $elements = $document->xpath("//*[contains(concat(' ', normalize-space(@class), ' '), ' post ')]");

        $this->assertTrue(is_array($elements));
        $this->assertEquals(3, count($elements));

        foreach ($elements as $element) {
            $this->assertInstanceOf('DiDom\Element', $element);
        }
    }

    public function testHtml()
    {
        $html = $this->loadFixture('posts.html');
        $document = new Document($html, false);

        $this->assertTrue(is_string($document->html()));
    }

    public function testHtmlWithOptions()
    {
        $html = '<html><body><span></span></body></html>';
        
        $document = new Document();
        $document->loadHtml($html);

        $this->assertEquals('<html><body><span/></body></html>', $document->html());
        $this->assertEquals('<html><body><span></span></body></html>', $document->html(LIBXML_NOEMPTYTAG));
    }

    public function testXml()
    {
        $xml = $this->loadFixture('books.xml');
        $document = new Document($xml, false, 'UTF-8', 'xml');

        $this->assertTrue(is_string($document->xml()));
    }

    public function testXmlWithOptions()
    {
        $xml = '<foo><bar></bar></foo>';
        
        $document = new Document();
        $document->loadXml($xml);

        $prolog = '<?xml version="1.0" encoding="UTF-8"?>'."\n";

        $this->assertEquals($prolog.'<foo><bar/></foo>', $document->xml());
        $this->assertEquals($prolog.'<foo><bar></bar></foo>', $document->xml(LIBXML_NOEMPTYTAG));
    }

    public function testFormat()
    {
        $html = $this->loadFixture('posts.html');
        $document = new Document($html, false);

        $this->assertFalse($document->getDocument()->formatOutput);

        $document->format();

        $this->assertTrue($document->getDocument()->formatOutput);
    }

    public function testText()
    {
        $html = '<html>foo</html>';
        $document = new Document($html, false);

        $this->assertEquals('foo', $document->text());
    }

    public function testGetType()
    {
        $document = new Document();

        $this->assertNull($document->getType());

        $html = $this->loadFixture('posts.html');
        $document = new Document($html);

        $this->assertEquals('html', $document->getType());

        $xml = $this->loadFixture('books.xml');
        $document = new Document($xml, false, 'UTF-8', 'xml');

        $this->assertEquals('xml', $document->getType());
    }

    public function testIs()
    {
        $html = $this->loadFixture('posts.html');

        $document = new Document($html, false);
        $document2 = new Document($html, false);

        $this->assertTrue($document->is($document));
        $this->assertFalse($document->is($document2));
    }

    public function testIsWithEmptyDocument()
    {
        $html = $this->loadFixture('posts.html');

        $document = new Document($html, false);
        $document2 = new Document();

        $this->assertFalse($document->is($document2));        
    }

    public function testGetDocument()
    {
        $domDocument = new \DOMDocument();
        $document = new Document($domDocument);

        $this->assertEquals($domDocument, $document->getDocument());
    }

    public function testGetElement()
    {
        $html = $this->loadFixture('posts.html');
        $document = new Document($html, false);

        $this->assertInstanceOf('DOMElement', $document->getElement());
    }

    public function testToElement()
    {
        $html = $this->loadFixture('posts.html');
        $document = new Document($html, false);

        $this->assertInstanceOf('DiDom\Element', $document->toElement());
    }

    public function testToStringHtml()
    {
        $html = $this->loadFixture('posts.html');
        $document = new Document($html, false);

        $this->assertEquals($document->html(), $document->__toString());
    }

    public function testToStringXml()
    {
        $xml = $this->loadFixture('books.xml');
        $document = new Document($xml, false, 'UTF-8', 'xml');

        $this->assertEquals($document->xml(), $document->__toString());
    }

    /**
     * @dataProvider findTests
     */
    public function testInvoke($html, $selector, $type, $count)
    {
        $document = new Document($html, false);
        $elements = $document($selector, $type);

        $this->assertTrue(is_array($elements));
        $this->assertEquals($count, count($elements));

        foreach ($elements as $element) {
            $this->assertInstanceOf('DiDom\Element', $element);
        }
    }
}
