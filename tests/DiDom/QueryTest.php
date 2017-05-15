<?php

namespace Tests\DiDom;

use Tests\TestCase;
use DiDom\Query;

class QueryTest extends TestCase
{
    /**
     * @dataProvider compileCssTests
     */
    public function testCompileCssSelector($selector, $xpath)
    {
        $this->assertEquals($xpath, Query::compile($selector));
    }

    /**
     * @dataProvider getSegmentsTests
     */
    public function testGetSegments($selector, $segments)
    {
        $this->assertEquals($segments, Query::getSegments($selector));
    }

    /**
     * @dataProvider buildXpathTests
     */
    public function testBuildXpath($segments, $xpath)
    {
        $this->assertEquals($xpath, Query::buildXpath($segments));
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testBuildXpathWithEmptyArray()
    {
        Query::buildXpath([]);
    }

    /**
     * @expectedException DiDom\Exceptions\InvalidSelectorException
     */
    public function testCompileWithEmptySelector()
    {
        Query::compile('');
    }

    /**
     * @expectedException DiDom\Exceptions\InvalidSelectorException
     */
    public function testGetSegmentsWithEmptySelector()
    {
        Query::compile('');
    }

    /**
     * @expectedException DiDom\Exceptions\InvalidSelectorException
     */
    public function testEmptyAttributeName()
    {
        Query::compile('input[=foo]');
    }

    /**
     * @expectedException DiDom\Exceptions\InvalidSelectorException
     */
    public function testUnknownPseudoClass()
    {
        Query::compile('li:unknown-pseudo-class');
    }

    /**
     * @expectedException DiDom\Exceptions\InvalidSelectorException
     */
    public function testEmptyNthExpression()
    {
        Query::compile('li:nth-child()');
    }

    /**
     * @expectedException DiDom\Exceptions\InvalidSelectorException
     */
    public function testUnknownNthExpression()
    {
        Query::compile('li:nth-child(foo)');
    }

    /**
     * @expectedException DiDom\Exceptions\InvalidSelectorException
     */
    public function testGetSegmentsWithEmptyClass()
    {
        Query::getSegments('.');
    }

    /**
     * @expectedException DiDom\Exceptions\InvalidSelectorException
     */
    public function testCompilehWithEmptyClass()
    {
        Query::compile('span.');
    }

    public function testCompileXpath()
    {
        $this->assertEquals('//div', Query::compile('//div', Query::TYPE_XPATH));
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testSetCompiledInvalidArgument()
    {
        Query::setCompiled('foo');
    }

    public function testSetCompiled()
    {
        $xpath = "//*[@id='foo']//*[contains(concat(' ', normalize-space(@class), ' '), ' bar ')]//baz";
        $compiled = ['#foo .bar baz' => $xpath];

        Query::setCompiled($compiled);

        $this->assertEquals($compiled, Query::getCompiled());
    }

    public function testGetCompiled()
    {
        Query::setCompiled([]);

        $selector = '#foo .bar baz';
        $xpath = '//*[@id="foo"]//*[contains(concat(" ", normalize-space(@class), " "), " bar ")]//baz';
        $compiled = [$selector => $xpath];

        Query::compile($selector);

        $this->assertEquals($compiled, Query::getCompiled());
    }

    public function compileCssTests()
    {
        $compiled = [
            ['a', '//a'],
            ['foo bar baz', '//foo//bar//baz'],
            ['foo > bar > baz', '//foo/bar/baz'],
            ['#foo', '//*[@id="foo"]'],
            ['.bar', '//*[contains(concat(" ", normalize-space(@class), " "), " bar ")]'],
            ['*[foo=bar]', '//*[@foo="bar"]'],
            ['*[foo="bar"]', '//*[@foo="bar"]'],
            ['*[foo=\'bar\']', '//*[@foo="bar"]'],
            ['select[name=category] option[selected=selected]', '//select[@name="category"]//option[@selected="selected"]'],
            ['*[^data-]', '//*[@*[starts-with(name(), "data-")]]'],
            ['*[^data-=foo]', '//*[@*[starts-with(name(), "data-")]="foo"]'],
            ['a[href^=https]', '//a[starts-with(@href, "https")]'],
            ['img[src$=png]', '//img[substring(@src, string-length(@src) - string-length("png") + 1) = "png"]'],
            ['a[href*=example.com]', '//a[contains(@href, "example.com")]'],
            ['script[!src]', '//script[not(@src)]'],
            ['a[href!="http://foo.com/"]', '//a[not(@href="http://foo.com/")]'],
            ['a[foo~="bar"]', '//a[contains(concat(" ", normalize-space(@foo), " "), " bar ")]'],
            ['input, textarea, select', '//input|//textarea|//select'],
            ['li:first-child', '//li[position() = 1]'],
            ['li:last-child', '//li[position() = last()]'],
            ['ul:empty', '//ul[count(descendant::*) = 0]'],
            ['ul:not-empty', '//ul[count(descendant::*) > 0]'],
            ['li:nth-child(odd)', '//*[(name()="li") and (position() mod 2 = 1 and position() >= 1)]'],
            ['li:nth-child(even)', '//*[(name()="li") and (position() mod 2 = 0 and position() >= 0)]'],
            ['li:nth-child(3)', '//*[(name()="li") and (position() = 3)]'],
            ['li:nth-child(-3)', '//*[(name()="li") and (position() = -3)]'],
            ['li:nth-child(3n)', '//*[(name()="li") and ((position() + 0) mod 3 = 0 and position() >= 0)]'],
            ['li:nth-child(3n+1)', '//*[(name()="li") and ((position() - 1) mod 3 = 0 and position() >= 1)]'],
            ['li:nth-child(3n-1)', '//*[(name()="li") and ((position() + 1) mod 3 = 0 and position() >= 1)]'],
            ['li:nth-child(n+3)', '//*[(name()="li") and ((position() - 3) mod 1 = 0 and position() >= 3)]'],
            ['li:nth-child(n-3)', '//*[(name()="li") and ((position() + 3) mod 1 = 0 and position() >= 3)]'],
            ['li:nth-of-type(odd)', '//li[position() mod 2 = 1 and position() >= 1]'],
            ['li:nth-of-type(even)', '//li[position() mod 2 = 0 and position() >= 0]'],
            ['li:nth-of-type(3)', '//li[position() = 3]'],
            ['li:nth-of-type(-3)', '//li[position() = -3]'],
            ['li:nth-of-type(3n)', '//li[(position() + 0) mod 3 = 0 and position() >= 0]'],
            ['li:nth-of-type(3n+1)', '//li[(position() - 1) mod 3 = 0 and position() >= 1]'],
            ['li:nth-of-type(3n-1)', '//li[(position() + 1) mod 3 = 0 and position() >= 1]'],
            ['li:nth-of-type(n+3)', '//li[(position() - 3) mod 1 = 0 and position() >= 3]'],
            ['li:nth-of-type(n-3)', '//li[(position() + 3) mod 1 = 0 and position() >= 3]'],
            ['ul:has(li.item)', '//ul[.//li[contains(concat(" ", normalize-space(@class), " "), " item ")]]'],
            ['form[name=register]:has(input[name=foo])', '//form[(@name="register") and (.//input[@name="foo"])]'],
            ['ul li a::text', '//ul//li//a/text()'],
            ['ul li a::text()', '//ul//li//a/text()'],
            ['ul li a::attr(href)', '//ul//li//a/@*[name() = "href"]'],
            ['ul li a::attr(href|title)', '//ul//li//a/@*[name() = "href" or name() = "title"]'],
            ['> ul li a', '/ul//li//a'],
        ];

        if (function_exists('mb_strtolower')) {
            $containsXpath = [
                ['li:contains(foo)', '//li[php:functionString("mb_strtolower", .) = php:functionString("mb_strtolower", "foo")]'],
                ['li:contains("foo")', '//li[php:functionString("mb_strtolower", .) = php:functionString("mb_strtolower", "foo")]'],
                ['li:contains(\'foo\')', '//li[php:functionString("mb_strtolower", .) = php:functionString("mb_strtolower", "foo")]'],
            ];
        } else {
            $containsXpath = [
                ['li:contains(foo)', '//li[php:functionString("strtolower", .) = php:functionString("mb_strtolower", "foo")]'],
                ['li:contains("foo")', '//li[php:functionString("strtolower", .) = php:functionString("mb_strtolower", "foo")]'],
                ['li:contains(\'foo\')', '//li[php:functionString("strtolower", .) = php:functionString("mb_strtolower", "foo")]'],
            ];
        }

        return $compiled;
    }

    public function buildXpathTests()
    {
        $xpath = [
            '//a',
            '//*[@id="foo"]',
            '//a[@id="foo"]',
            '//a[contains(concat(" ", normalize-space(@class), " "), " foo ")]',
            '//a[(contains(concat(" ", normalize-space(@class), " "), " foo ")) and (contains(concat(" ", normalize-space(@class), " "), " bar "))]',
            '//a[@href]',
            '//a[@href="http://example.com/"]',
            '//a[(@href="http://example.com/") and (@title="Example Domain")]',
            '//a[(@target="_blank") and (starts-with(@href, "https"))]',
            '//a[substring(@href, string-length(@href) - string-length(".com") + 1) = ".com"]',
            '//a[contains(@href, "example")]',
            '//a[not(@href="http://foo.com/")]',
            '//script[not(@src)]',
            '//li[position() = 1]',
            '//*[(@id="id") and (contains(concat(" ", normalize-space(@class), " "), " foo ")) and (@name="value") and (position() = 1)]',
        ];

        $segments = [
            ['tag' => 'a'],
            ['id' => 'foo'],
            ['tag' => 'a', 'id' => 'foo'],
            ['tag' => 'a', 'classes' => ['foo']],
            ['tag' => 'a', 'classes' => ['foo', 'bar']],
            ['tag' => 'a', 'attributes' => ['href' => null]],
            ['tag' => 'a', 'attributes' => ['href' => 'http://example.com/']],
            ['tag' => 'a', 'attributes' => ['href' => 'http://example.com/', 'title' => 'Example Domain']],
            ['tag' => 'a', 'attributes' => ['target' => '_blank', 'href^' => 'https']],
            ['tag' => 'a', 'attributes' => ['href$' => '.com']],
            ['tag' => 'a', 'attributes' => ['href*' => 'example']],
            ['tag' => 'a', 'attributes' => ['href!' => 'http://foo.com/']],
            ['tag' => 'script', 'attributes' => ['!src' => null]],
            ['tag' => 'li', 'pseudo' => 'first-child'],
            ['tag' => '*', 'id' => 'id', 'classes' => ['foo'], 'attributes' => ['name' => 'value'], 'pseudo' => 'first-child', 'rel' => '>'],
        ];

        $parameters = [];

        foreach ($segments as $index => $segment) {
            $parameters[] = [$segment, $xpath[$index]];
        }

        return $parameters;
    }

    public function getSegmentsTests()
    {
        $segments = [
            ['selector' => 'a', 'tag' => 'a'],
            ['selector' => '#foo', 'id' => 'foo'],
            ['selector' => 'a#foo', 'tag' => 'a', 'id' => 'foo'],
            ['selector' => 'a.foo', 'tag' => 'a', 'classes' => ['foo']],
            ['selector' => 'a.foo.bar', 'tag' => 'a', 'classes' => ['foo', 'bar']],
            ['selector' => 'a[href]', 'tag' => 'a', 'attributes' => ['href' => null]],
            ['selector' => 'a[href=http://example.com/]', 'tag' => 'a', 'attributes' => ['href' => 'http://example.com/']],
            ['selector' => 'a[href="http://example.com/"]', 'tag' => 'a', 'attributes' => ['href' => 'http://example.com/']],
            ['selector' => 'a[href=\'http://example.com/\']', 'tag' => 'a', 'attributes' => ['href' => 'http://example.com/']],
            ['selector' => 'a[href=http://example.com/][title=Example Domain]', 'tag' => 'a', 'attributes' => ['href' => 'http://example.com/', 'title' => 'Example Domain']],
            ['selector' => 'a[href=http://example.com/][href=http://example.com/404]', 'tag' => 'a', 'attributes' => ['href' => 'http://example.com/404']],
            ['selector' => 'a[href^=https]', 'tag' => 'a', 'attributes' => ['href^' => 'https']],
            ['selector' => 'li:first-child', 'tag' => 'li', 'pseudo' => 'first-child'],
            ['selector' => 'ul >', 'tag' => 'ul', 'rel' => '>'],
            ['selector' => '#id.foo[name=value]:first-child >', 'id' => 'id', 'classes' => ['foo'], 'attributes' => ['name' => 'value'], 'pseudo' => 'first-child', 'rel' => '>'],
            ['selector' => 'li.bar:nth-child(2n)', 'tag' => 'li', 'classes' => ['bar'], 'pseudo' => 'nth-child', 'expr' => '2n'],
        ];

        $parameters = [];

        foreach ($segments as $segment) {
            $parameters[] = [$segment['selector'], $segment];
        }

        return $parameters;
    }
}
