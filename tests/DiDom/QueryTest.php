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
}
