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

    public function testGetCompiled()
    {
        Query::setCompiled([]);
        
        $selector = '.post h2';
        $xpath    = Query::compile($selector);
        $compiled = Query::getCompiled();

        $this->assertTrue(is_array($compiled));
        $this->assertEquals(1, count($compiled));
        $this->assertTrue(array_key_exists($selector, $compiled));
        $this->assertEquals($xpath, $compiled[$selector]);
    }
}
