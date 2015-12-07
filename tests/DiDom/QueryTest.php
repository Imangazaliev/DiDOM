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
     * @expectedException InvalidArgumentException
     */
    public function testGetSegmentsWithEmptySelector()
    {
        Query::compile('');
    }

    /**
     * @expectedException RuntimeException
     */
    public function testUnknownPseudoClass()
    {
        Query::compile('li:unknown-pseudo-class');
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
            ['#foo', '//*[@id="foo"]'],
            ['.bar', '//*[contains(concat(" ", normalize-space(@class), " "), " bar ")]'],
            ['foo bar baz', '//foo//bar//baz'],
            ['foo > bar > baz', '//foo/bar/baz'],
            ['input, textarea, select', '//input|//textarea|//select'],
            ['li:first-child', '//li[1]'],
            ['li:last-child', '//li[last()]'],
            ['ul:empty', '//ul[count(descendant::*) = 0]'],
            ['ul:not-empty', '//ul[count(descendant::*) > 0]'],
            ['li:nth-child(odd)', '//li[(position() -1) mod 2 = 0 and position() >= 1]'],
            ['li:nth-child(even)', '//li[position() mod 2 = 0 and position() >= 0]'],
            ['li:nth-child(3)', '//li[position() = 3]'],
            ['li:nth-child(-3)', '//li[position() = -3]'],
            ['li:nth-child(3n)', '//li[(position() + 0) mod 3 = 0 and position() >= 0]'],
            ['li:nth-child(3n+1)', '//li[(position() - 1) mod 3 = 0 and position() >= 1]'],
            ['li:nth-child(3n-1)', '//li[(position() + 1) mod 3 = 0 and position() >= 1]'],
            ['li:nth-child(n+3)', '//li[(position() - 3) mod 1 = 0 and position() >= 3]'],
            ['li:nth-child(n-3)', '//li[(position() + 3) mod 1 = 0 and position() >= 3]'],
        ];

        return $compiled;
    }

    public function getSegmentsTests()
    {
        $segments = [
            ['selector' => 'a', 'tag' => 'a'],
            ['selector' => '#foo', 'tag' => '*', 'id' => 'foo'],
            ['selector' => 'a#foo', 'tag' => 'a', 'id' => 'foo'],
            ['selector' => 'a.foo', 'tag' => 'a', 'classes' => ['foo']],
            ['selector' => 'a.foo.bar', 'tag' => 'a', 'classes' => ['foo', 'bar']],
            ['selector' => 'a[href]', 'tag' => 'a', 'attributes' => ['href' => null]],
            ['selector' => 'a[href=http://example.com/]', 'tag' => 'a', 'attributes' => ['href' => 'http://example.com/']],
            ['selector' => 'a[href=http://example.com/][title=Example Domain]', 'tag' => 'a', 'attributes' => ['href' => 'http://example.com/', 'title' => 'Example Domain']],
            ['selector' => 'a[href=http://example.com/][href=http://example.com/404]', 'tag' => 'a', 'attributes' => ['href' => 'http://example.com/404']],
            ['selector' => 'a[href="http://example.com/"]', 'tag' => 'a', 'attributes' => ['href' => 'http://example.com/']],
            ['selector' => 'a[href=\'http://example.com/\']', 'tag' => 'a', 'attributes' => ['href' => 'http://example.com/']],
            ['selector' => 'li:first-child', 'tag' => 'li', 'pseudo' => 'first-child'],
            ['selector' => 'ul >', 'tag' => 'ul', 'rel' => '>'],
            ['selector' => '#id.foo[name=value]:first-child >', 'tag' => '*', 'id' => 'id', 'classes' => ['foo'], 'attributes' => ['name' => 'value'], 'pseudo' => 'first-child', 'rel' => '>'],
        ];

        $parameters = [];

        foreach ($segments as $segment) {
            $parameters[] = [$segment['selector'], $segment];
        }

        return $parameters;
    }
}
