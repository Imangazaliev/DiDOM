<?php

namespace Tests\DiDom;

use Tests\TestCase;
use DiDom\Document;
use DiDom\Query;

class DocumentTest extends TestCase
{
    public function testLoadHtmlException()
    {
        $this->setExpectedException('InvalidArgumentException');

        $document = new Document();
        $document->loadHtml(array('element'));
    }

    /**
     * @dataProvider loadHtmlFileProvider
     */
    public function testLoadHtmlFileException($filename, $type)
    {
        $this->setExpectedException($type);

        $document = new Document('', true);
        $document->loadHtmlFile($filename);
    }

    public function testCreateElement()
    {
        $html = $this->loadFixture('posts.html');

        $document = new Document($html, false);
        $element  = $document->createElement('span', 'value');

        $this->assertInstanceOf('DiDom\Element', $element);
        $this->assertEquals('span', $element->tag);
        $this->assertEquals('value', $element->text());

        $element = $document->createElement('span');
        $this->assertEquals('', $element->text());
    }

    public function testAppendChildException()
    {
        $this->setExpectedException('InvalidArgumentException');

        $document = new Document('', true);
        $document->appendChild(null);
    }

    /**
     * @dataProvider findProvider
     */
    public function testFind($html, $selector, $type, $count)
    {
        $document = new Document($html, false);
        $elements = $document->find($selector, $type);

        $this->assertTrue(is_array($elements));
        $this->assertEquals($count, count($elements));
    }

    public function testXpath()
    {
        $html = $this->loadFixture('posts.html');

        $document = new Document($html, false);
        $elements = $document->xpath("//*[contains(concat(' ', normalize-space(@class), ' '), ' post ')]");

        $this->assertTrue(is_array($elements));
        $this->assertEquals(3, count($elements));
    }

    public function testHas()
    {
        $html = $this->loadFixture('posts.html');

        $document = new Document($html, false);

        $this->assertTrue($document->has('.posts'));
        $this->assertFalse($document->has('.fake'));
    }

    public function testHtml()
    {
        $html = $this->loadFixture('posts.html');

        $document = new Document($html, false);

        $this->assertTrue(is_string($document->html()));
    }

    public function testFormat()
    {
        $html = $this->loadFixture('posts.html');

        $document = new Document($html, false);

        $domDocument = $document->getDocument();
        $this->assertFalse($domDocument->formatOutput);

        $document->format();

        $domDocument = $document->getDocument();
        $this->assertTrue($domDocument->formatOutput);
    }

    public function testText()
    {
        $html = $this->loadFixture('posts.html');

        $document = new Document($html, false);

        $this->assertTrue(is_string($document->text()));
    }

    /**
     * @dataProvider findProvider
     */
    public function testInvoke($html, $selector, $type, $count)
    {
        $document = new Document($html, false);
        $elements = $document($selector, $type);

        $this->assertTrue(is_array($elements));
        $this->assertEquals($count, count($elements));
    }

    public function testIs()
    {
        $html = $this->loadFixture('posts.html');
        
        $document = new Document($html, false);

        $this->assertTrue($document->is($document));
    }

    public function testIsException()
    {
        $this->setExpectedException('InvalidArgumentException');

        $document = new Document();

        $document->is(null);
    }

    public function testGetElement()
    {
        $html = $this->loadFixture('posts.html');

        $document = new Document($html, false);

        $domElement = $document->getElement();

        $this->assertInstanceOf('DOMElement', $domElement);
    }

    public function testToElement()
    {
        $html = $this->loadFixture('posts.html');

        $document = new Document($html, false);

        $element = $document->toElement();

        $this->assertInstanceOf('DiDom\Element', $element);
    }

    public function loadHtmlFileProvider()
    {
        return array(
            array(array('element'), 'InvalidArgumentException'),
            array('path/to/file', 'RuntimeException'),
        );
    }

    public function findProvider()
    {
        $html = $this->loadFixture('posts.html');

        return array(
            array($html, '.post h2', Query::TYPE_CSS, 3),
            array($html, '.fake h2', Query::TYPE_CSS, 0),
            array($html, '.post h2, .post p', Query::TYPE_CSS, 6),
            array($html, "//*[contains(concat(' ', normalize-space(@class), ' '), ' post ')]", Query::TYPE_XPATH, 3),
        );
    }
}
