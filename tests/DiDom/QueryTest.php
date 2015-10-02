<?php

namespace Tests\DiDom;

use Tests\TestCase;
use DiDom\Query;

class QueryTest extends TestCase
{
    public function testSetCompiledException()
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
        $xpath = "//*[contains(concat(' ', normalize-space(@class), ' '), ' post ')]";
        $compiled = [$selector => $xpath];

        $xpath    = Query::compile($selector);
        $compiled = Query::getCompiled();

        $this->assertEquals($compiled, Query::getCompiled());
    }
}
