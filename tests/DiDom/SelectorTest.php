<?php

namespace Tests\DiDom;

use Tests\TestCase;
use DiDom\Document;
use DiDom\Query;

class SelectorTest extends TestCase
{
    public function testTag()
    {
        $document = $this->getList();

        $expected = ['Item 1', 'Item 2', 'Item 3', 'Item 1', 'Item 2', 'Item 3'];

        $result = [];

        foreach ($document->find('li') as $element) {
            $result[] = $element->text();
        }

        $this->assertEquals($expected, $result);
    }

    public function testNestedTag()
    {
        $document = $this->getList();

        $expected = ['Item 1', 'Item 2', 'Item 3'];

        $result = [];

        foreach ($document->find('ul a') as $element) {
            $result[] = $element->text();
        }

        $this->assertEquals($expected, $result);
    }

    public function testDirectChild()
    {
        $html = '
            <div>
                <p><span>Lorem ipsum.</span></p>
                <span>Lorem ipsum.</span>
            </div>
        ';

        $document = new Document($html);

        $expected = ['Lorem ipsum.'];

        $result = [];

        foreach ($document->find('div > span') as $element) {
            $result[] = $element->text();
        }

        $this->assertEquals($expected, $result);
    }

    public function testId()
    {
        $html = '
            <span>Lorem ipsum dolor.</span>
            <span id="second">Tenetur totam, nostrum.</span>
            <span>Iste, doloremque, praesentium.</span>
        ';

        $document = new Document($html);

        $expected = ['Tenetur totam, nostrum.'];

        $result = [];

        foreach ($document->find('#second') as $element) {
            $result[] = $element->text();
        }

        $this->assertEquals($expected, $result);
    }

    public function testClass()
    {
        $html = '
            <span class="odd first">Lorem ipsum dolor.</span>
            <span class="even second">Tenetur totam, nostrum.</span>
            <span class="odd third">Iste, doloremque, praesentium.</span>
        ';

        $document = new Document($html);

        $expected = ['Lorem ipsum dolor.', 'Iste, doloremque, praesentium.'];

        $result = [];

        foreach ($document->find('.odd') as $element) {
            $result[] = $element->text();
        }

        $this->assertEquals($expected, $result);

        $expected = ['Iste, doloremque, praesentium.'];

        $result = [];

        foreach ($document->find('.odd.third') as $element) {
            $result[] = $element->text();
        }

        $this->assertEquals($expected, $result);
    }

    protected function getList()
    {
        $html = '
            <ul id="first">
                <li><a href="#">Item 1</a></li>
                <li><a href="#">Item 2</a></li>
                <li><a href="#">Item 3</a></li>
            </ul>
            <ol id="second">
                <li><a href="#">Item 1</a></li>
                <li><a href="#">Item 2</a></li>
                <li><a href="#">Item 3</a></li>
            </ol>
        ';

        return new Document($html);
    }
}