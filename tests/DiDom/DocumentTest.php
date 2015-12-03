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
    public function testLoadHtmlException()
    {
        $document = new Document();
        $document->loadHtml(null);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testAppendChildException()
    {
        $document = new Document('');
        $document->appendChild(null);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testIsException()
    {
        $document = new Document();
        $document->is(null);
    }

    /**
     * @dataProvider loadHtmlFileTests
     */
    public function testLoadHtmlFileException($filename, $type)
    {
        $this->setExpectedException($type);

        $document = new Document('');
        $document->loadHtmlFile($filename);
    }

    /**
     * @dataProvider loadHtmlCharsetTests
     */
    public function testLoadHtmlCharset($html, $text)
    {
        $document = new Document('');
        $document->loadHtml($html);

        $this->assertEquals($text, $document->find('div')[0]->text());
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

    public function testCreateElement()
    {
        $html = $this->loadFixture('posts.html');

        $document = new Document($html, false);
        $element  = $document->createElement('span', 'value');

        $this->assertInstanceOf('DiDom\Element', $element);
        $this->assertEquals('span', $element->getElement()->tagName);
        $this->assertEquals('value', $element->getElement()->textContent);

        $element = $document->createElement('span');
        $this->assertEquals('', $element->text());

        $element = $document->createElement('input', '', ['name' => 'username']);
        $this->assertEquals('input', $element->getElement()->tagName);
        $this->assertEquals('username', $element->getElement()->getAttribute('name'));
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

        if ($count > 0) {
            $this->assertInstanceOf('DiDom\Element', $elements[0]);
        }
    }

    /**
     * @dataProvider findTests
     */
    public function testReturnDomElement($html, $selector, $type, $count)
    {
        $document = new Document($html, false);
        $elements = $document->find($selector, $type, false);

        $this->assertTrue(is_array($elements));
        $this->assertEquals($count, count($elements));

        if ($count > 0) {
            $this->assertInstanceOf('DOMElement', $elements[0]);
        }
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
     * @dataProvider findTests
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

    public function loadHtmlFileTests()
    {
        return array(
            array(array('element'), 'InvalidArgumentException'),
            array('path/to/file', 'RuntimeException'),
        );
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
}
