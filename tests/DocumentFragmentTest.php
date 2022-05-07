<?php

declare(strict_types=1);

namespace DiDom\Tests;

use DiDom\Document;

class DocumentFragmentTest extends TestCase
{
    public function testAppendXml()
    {
        $document = new Document();

        $documentFragment = $document->createDocumentFragment();

        $documentFragment->appendXml('<foo>bar</foo>');

        $this->assertEquals('<foo>bar</foo>', $documentFragment->innerXml());
    }
}
