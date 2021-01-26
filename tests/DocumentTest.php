<?php

namespace DiDom\Tests;

use DiDom\Document;
use DiDom\Query;
use InvalidArgumentException;
use RuntimeException;

class DocumentTest extends TestCaseDiDom
{
    /**
     */
    public function testConstructWithInvalidArgument()
    {
        $this->expectException(\InvalidArgumentException::class, 'DiDom\\Document::load expects parameter 1 to be string, array given');

        new Document(array('foo'));
    }

    /**
     */
    public function testConstructWithInvalidEncoding()
    {
        $this->expectException(\InvalidArgumentException::class, 'DiDom\\Document::__construct expects parameter 3 to be string, NULL given');

        new Document(null, false, null);
    }

    /**
     */
    public function testConstructWithNotExistingFile()
    {
        $this->expectException(\RuntimeException::class, 'Could not load file path/to/file');

        new Document('path/to/file', true);
    }

    /**
     */
    public function testConstructorWithInvalidTypeOfDocumentTypeArgument()
    {
        $this->expectException(\InvalidArgumentException::class, 'DiDom\\Document::load expects parameter 3 to be string, NULL given');

        new Document('foo', false, 'UTF-8', null);
    }

    /**
     */
    public function testConstructorWithInvalidDocumentType()
    {
        $this->expectException(\RuntimeException::class, 'Document type must be "xml" or "html", bar given');

        new Document('foo', false, 'UTF-8', 'bar');
    }

    /**
     * @dataProvider loadHtmlCharsetTests
     */
    public function testLoadHtmlCharset($html, $text)
    {
        $document = new Document($html, false, 'UTF-8');

        $this->assertEquals($text, $document->first('div')->text());
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

    public function testCreate()
    {
        $this->assertInstanceOf('DiDom\Document', Document::create());
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

    public function testCreateElementBySelector()
    {
        $document = new Document();

        $element = $document->createElementBySelector('a.external-link[href=http://example.com]');

        $this->assertEquals('a', $element->tag);
        $this->assertEquals('', $element->text());
        $this->assertEquals(['href' => 'http://example.com', 'class' => 'external-link'], $element->attributes());

        $element = $document->createElementBySelector('#block', 'Foo');

        $this->assertEquals('div', $element->tag);
        $this->assertEquals('Foo', $element->text());
        $this->assertEquals(['id' => 'block'], $element->attributes());

        $element = $document->createElementBySelector('input', null, ['name' => 'name', 'placeholder' => 'Enter your name']);

        $this->assertEquals('input', $element->tag);
        $this->assertEquals('', $element->text());
        $this->assertEquals(['name' => 'name', 'placeholder' => 'Enter your name'], $element->attributes());
    }

    public function testCreateTextNode()
    {
        $document = new Document();

        $textNode = $document->createTextNode('foo bar baz');

        $this->assertInstanceOf('DiDom\Element', $textNode);
        $this->assertInstanceOf('DOMText', $textNode->getNode());
        $this->assertEquals('foo bar baz', $textNode->text());
    }

    public function testCreateComment()
    {
        $document = new Document();

        $comment = $document->createComment('foo bar baz');

        $this->assertInstanceOf('DiDom\Element', $comment);
        $this->assertInstanceOf('DOMComment', $comment->getNode());
        $this->assertEquals('foo bar baz', $comment->text());
    }

    public function testCreateCdataSection()
    {
        $document = new Document();

        $cdataSection = $document->createCdataSection('foo bar baz');

        $this->assertInstanceOf('DiDom\Element', $cdataSection);
        $this->assertInstanceOf('DOMCdataSection', $cdataSection->getNode());
        $this->assertEquals('foo bar baz', $cdataSection->text());
    }

    public function testCreateDocumentFragment()
    {
        $document = new Document();

        $documentFragment = $document->createDocumentFragment();

        $this->assertInstanceOf('DiDom\DocumentFragment', $documentFragment);
        $this->assertInstanceOf('DOMDocumentFragment', $documentFragment->getNode());
    }

    /**
     */
    public function testAppendChildWithInvalidArgument()
    {
        $this->expectException(\InvalidArgumentException::class, 'Argument 1 passed to DiDom\\Document::appendChild must be an instance of DiDom\\Element or DOMNode, string given');

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
        $appendedChild = $document->appendChild($node);

        $this->assertCount(1, $document->find('span'));
        $this->assertTrue($appendedChild->is($document->first('span')));

        $appendedChild->remove();

        $this->assertCount(0, $document->find('span'));

        $nodes = [];
        $nodes[] = $document->createElement('span');
        $nodes[] = $document->createElement('span');

        $appendedChildren = $document->appendChild($nodes);

        $nodes = $document->find('span');

        $this->assertCount(2, $appendedChildren);
        $this->assertCount(2, $nodes);

        foreach ($appendedChildren as $index => $child) {
            $this->assertTrue($child->is($nodes[$index]));
        }
    }

    /**
     */
    public function testLoadWithInvalidContentArgument()
    {
        $this->expectException(\InvalidArgumentException::class, 'DiDom\\Document::load expects parameter 1 to be string, NULL given');

        $document = new Document();

        $document->load(null);
    }

    /**
     */
    public function testLoadWithNotExistingFile()
    {
        $this->expectException(\RuntimeException::class, 'Could not load file path/to/file');

        $document = new Document();

        $document->load('path/to/file', true);
    }

    /**
     */
    public function testLoadWithInvalidTypeOfDocumentTypeArgument()
    {
        $this->expectException(\InvalidArgumentException::class, 'DiDom\\Document::load expects parameter 3 to be string, NULL given');

        $document = new Document();

        $document->load('foo', false, null);
    }

    /**
     */
    public function testLoadWithInvalidDocumentType()
    {
        $this->expectException(\RuntimeException::class, 'Document type must be "xml" or "html", bar given');

        $document = new Document();

        $document->load('foo', false, 'bar');
    }

    /**
     */
    public function testLoadWithInvalidOptionsType()
    {
        $this->expectException(\InvalidArgumentException::class, 'DiDom\\Document::load expects parameter 4 to be integer, string given');

        $document = new Document();

        $document->load('foo', false, 'html', 'bar');
    }

    public function testLoadHtmlDocument()
    {
        $html = '
            <!DOCTYPE html>
            <html>
            <head>
                <title>Document</title>
            </head>
            <body>
                <div class="foo">Foo — Bar — Baz</div>
            </body>
            </html>
        ';

        $document = new Document();

        $document->load($html, false, 'html');

        $this->assertEquals('Foo — Bar — Baz', $document->first('.foo')->text());
    }

    public function testLoadXmlDocument()
    {
        $xml = '
            <?xml version="1.0" encoding="UTF-8"?>
            <root>
                <foo>Foo — Bar — Baz</foo>
            </root>
        ';

        $document = new Document();

        $document->load($xml, false, 'xml');

        $this->assertEquals('Foo — Bar — Baz', $document->first('foo')->text());
    }

    /**
     */
    public function testLoadHtmlWithInvalidArgument()
    {
        $this->expectException(\InvalidArgumentException::class, 'DiDom\\Document::load expects parameter 1 to be string, NULL given');

        $document = new Document();

        $document->loadHtml(null);
    }

    /**
     */
    public function testLoadHtmlFileWithInvalidArgument()
    {
        $this->expectException(\InvalidArgumentException::class, 'DiDom\\Document::load expects parameter 1 to be string, array given');

        $document = new Document();

        $document->loadHtmlFile(array('foo'));
    }

    /**
     */
    public function testLoadHtmlFileWithNotExistingFile()
    {
        $this->expectException(\RuntimeException::class, 'Could not load file path/to/file');

        $document = new Document();

        $document->loadHtmlFile('path/to/file');
    }

    /**
     */
    public function testLoadXmlWithInvalidArgument()
    {
        $this->expectException(\InvalidArgumentException::class, 'DiDom\\Document::load expects parameter 1 to be string, NULL given');

        $document = new Document();

        $document->loadXml(null);
    }

    /**
     */
    public function testLoadXmlFileWithInvalidArgument()
    {
        $this->expectException(\InvalidArgumentException::class, 'DiDom\\Document::load expects parameter 1 to be string, array given');

        $document = new Document();

        $document->loadXmlFile(array('foo'));
    }

    /**
     */
    public function testLoadXmlFileWithNotExistingFile()
    {
        $this->expectException(\RuntimeException::class, 'Could not load file path/to/file');

        $document = new Document();

        $document->loadXmlFile('path/to/file');
    }

    public function testHas()
    {
        $document = new Document($this->loadFixture('posts.html'));

        $this->assertTrue($document->has('.posts'));
        $this->assertFalse($document->has('.fake'));
    }

    /**
     * @dataProvider findTests
     */
    public function testFind($html, $selector, $type, $count)
    {
        $document = new Document($html);
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
        $document = new Document($html);
        $elements = $document->find($selector, $type, false);

        $this->assertTrue(is_array($elements));
        $this->assertEquals($count, count($elements));

        foreach ($elements as $element) {
            $this->assertInstanceOf('DOMElement', $element);
        }
    }

    public function testFindWithContext()
    {
        $document = new Document($this->loadFixture('posts.html'));

        $post = $document->find('.post')[1];
        $title = $document->find('.post .title')[1];

        $titleInContext = $document->find('.title', Query::TYPE_CSS, true, $post)[0];

        $this->assertTrue($title->is($titleInContext));
        $this->assertFalse($title->is($post->find('.title')[0]));
    }

    public function testFindText()
    {
        $html = $this->loadFixture('menu.html');

        $document = new Document($html);
        $texts = $document->find('//a/text()', Query::TYPE_XPATH);

        $this->assertTrue(is_array($texts));
        $this->assertEquals(3, count($texts));

        $this->assertEquals(['Link 1', 'Link 2', 'Link 3'], $texts);
    }

    public function testFindComment()
    {
        $html = $this->loadFixture('menu.html');

        $document = new Document($html);

        $comment = $document->xpath('/html/body/ul/li/a/comment()');
        $this->assertTrue($comment[0]->isCommentNode());
        $this->assertTrue($comment[1]->isCommentNode());

        $this->assertTrue(is_array($comment));
        $this->assertEquals(2, count($comment));

        $comment = $document->xpath('/html/body/comment()');
        $this->assertTrue($comment[0]->isCommentNode());

        $this->assertTrue(is_array($comment));
        $this->assertEquals(1, count($comment));
    }

    public function testFindAttribute()
    {
        $html = $this->loadFixture('menu.html');

        $document = new Document($html);
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

        $this->assertEquals('One', $document->first('ul > li::text'));

        $document = new Document();

        $this->assertNull($document->first('ul > li'));
    }

    public function testFirstWithContext()
    {
        $html = '
            <div class="root">
                <span>Foo</span>

                <div><span>Bar</span></div>
            </div>
        ';

        $document = new Document($html);

        $div = $document->first('.root div');
        $span = $document->first('.root div span');

        $result = $document->first('span', Query::TYPE_CSS, true, $div);

        $this->assertTrue($span->is($result));
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

    public function testCount()
    {
        $html = '<ul><li>One</li><li>Two</li><li>Three</li></ul>';

        $document = new Document($html, false);

        $this->assertIsInt($document->count('li'));
        $this->assertEquals(3, $document->count('li'));

        $document = new Document();

        $this->assertIsInt($document->count('li'));
        $this->assertEquals(0, $document->count('li'));
    }

    public function testCreateXpath()
    {
        $document = new Document();

        $xpath =$document->createXpath();

        $this->assertInstanceOf('DOMXPath', $xpath);
        $this->assertEquals($document->getDocument(), $xpath->document);
    }

    public function testHtml()
    {
        $html = '
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Document</title>
</head>
<body>
English language <br>
Русский язык <br>
اللغة العربية <br>
漢語 <br>
Tiếng Việt <br>

&lt; &gt;
</body>
</html>
        ';

        $document = new Document($html);

        $this->assertEquals(trim($html), $document->html());
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

    /**
     */
    public function testIsWithInvalidArgument()
    {
        $this->expectException(\InvalidArgumentException::class);

        $document = new Document();
        $document->is(null);
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

    public function testGetType()
    {
        // empty document

        $document = new Document();

        $this->assertNull($document->getType());

        // html

        $html = $this->loadFixture('posts.html');

        $document = new Document($html);
        $this->assertEquals('html', $document->getType());

        $document = new Document();
        $document->loadHtml($html);
        $this->assertEquals('html', $document->getType());

        $document = new Document();
        $document->load($html, false, 'html');
        $this->assertEquals('html', $document->getType());

        // xml

        $xml = $this->loadFixture('books.xml');

        $document = new Document($xml, false, 'UTF-8', 'xml');
        $this->assertEquals('xml', $document->getType());

        $document = new Document();
        $document->loadXml($xml);
        $this->assertEquals('xml', $document->getType());

        $document = new Document();
        $document->load($xml, false, 'xml');
        $this->assertEquals('xml', $document->getType());
    }

    public function testGetEncoding()
    {
        $document = new Document();

        $this->assertEquals('UTF-8', $document->getEncoding());

        $document = new Document(null, false, 'CP-1251');

        $this->assertEquals('CP-1251', $document->getEncoding());
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

    /**
     */
    public function testEmptyDocumentToElement()
    {
        $this->expectException(\RuntimeException::class);

        $document = new Document();

        $document->toElement();
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
