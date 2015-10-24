<?php

namespace Tests\DiDom;

use Tests\TestCase;
use DiDom\Query;

class QueryTest extends TestCase
{
    /**
     * @dataProvider compiledCssProvider
     */
    public function testCompileCssSelector($selector, $xpath)
    {
        $this->assertEquals($xpath, Query::compile($selector));
    }

    /**
     * @dataProvider segmentsProvider
     */
    public function testGetSegments($selector, $segments)
    {
        $this->assertEquals($segments, Query::getSegments($selector));
    }

    /**
     * @dataProvider invalidSelectorProvider
     */
    public function testInvalidSelector($selector)
    {
        $this->setExpectedException('RuntimeException');

        Query::cssToXpath($selector);
    }

    public function testCompileXpath()
    {
        $this->assertEquals('xpath-expression', Query::compile('xpath-expression', Query::TYPE_XPATH));
    }

    public function testSetCompiledInvalidArgument()
    {
        $this->setExpectedException('InvalidArgumentException');

        Query::setCompiled('test');
    }

    public function testSetCompiled()
    {
        $xpath = "//*[contains(concat(' ', normalize-space(@class), ' '), ' post ')]";
        $compiled = ['.post h2' => $xpath];

        Query::setCompiled($compiled);

        $this->assertEquals($compiled, Query::getCompiled());
    }

    public function testGetCompiled()
    {
        Query::setCompiled([]);

        $selector = '.post h2';
        $xpath = '//*[contains(concat(" ", normalize-space(@class), " "), " post ")]//h2';
        $compiled = [$selector => $xpath];

        Query::compile($selector);

        $this->assertEquals($compiled, Query::getCompiled());
    }

    public function compiledCssProvider()
    {
        $compiled = [
            ['h2', '//h2'],
            ['.post h2', '//*[contains(concat(" ", normalize-space(@class), " "), " post ")]//h2'],
            ['.post, h2', '//*[contains(concat(" ", normalize-space(@class), " "), " post ")]|//h2'],
            ['div#layout', "//div[@id='layout']"],
        ];

        return $compiled;
    }

    public function segmentsProvider() {
        $segments = [
            ['selector' => 'h1', 'tag' => 'h1'],
            ['selector' => 'div#content', 'tag' => 'div', 'id' => 'content'],
            ['selector' => 'button.big.active', 'tag' => 'button', 'classes' => ['big', 'active']],
            ['selector' => 'input[type=text][name=email]', 'tag' => 'input', 'attributes' => ['type' => 'text','name' => 'email']],
            ['selector' => 'textarea[name=product-description]', 'tag' => 'textarea', 'attributes' => ['name' => 'product-description']],
            ['selector' => 'li.item:first-child', 'tag' => 'li', 'classes' => ['item'], 'pseudo' => 'first-child'],
            ['selector' => 'li.item:nth-child(odd)', 'tag' => 'li', 'classes' => ['item'], 'pseudo' => 'nth-child', 'expr' => 'odd'],
            ['selector' => 'div#body >', 'tag' => 'div', 'id' => 'body', 'rel' => '>'],
            ['selector' => '#id.class[name=value]:last-child >', 'tag' => '*', 'id' => 'id', 'classes' => ['class'], 'attributes' => ['name' => 'value'], 'pseudo' => 'last-child', 'rel' => '>'],
        ];

        $parameters = [];

        foreach ($segments as $segment) {
            $parameters[] = [$segment['selector'], $segment];
        }

        return $parameters;
    }

    public function invalidSelectorProvider()
    {
        $selectors = [
            ['li:unknown-pseudo-class'],
        ];

        return $selectors;
    }
}
