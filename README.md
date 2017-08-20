# DiDOM

[![Build Status](https://codeship.com/projects/cf938980-36f0-0134-119e-36dc468776c7/status?branch=master)](https://codeship.com/projects/165662)
[![Total Downloads](https://poser.pugx.org/imangazaliev/didom/downloads)](https://packagist.org/packages/imangazaliev/didom)
[![Latest Stable Version](https://poser.pugx.org/imangazaliev/didom/v/stable)](https://packagist.org/packages/imangazaliev/didom)
[![License](https://poser.pugx.org/imangazaliev/didom/license)](https://packagist.org/packages/imangazaliev/didom)

[README на русском](README-RU.md)

DiDOM - simple and fast HTML parser.

## Contents

- [Installation](#installation)
- [Quick start](#quick-start)
- [Creating new document](#creating-new-document)
- [Search for elements](#search-for-elements)
- [Verify if element exists](#verify-if-element-exists)
- [Supported selectors](#supported-selectors)
- [Output](#output)
- [Creating a new element](#creating-a-new-element)
- [Getting parent element](#getting-parent-element)
- [Getting sibling elements](#getting-sibling-elements)
- [Getting the child elements](#getting-the-child-elements)
- [Getting document](#getting-document)
- [Working with element attributes](#working-with-element-attributes)
- [Comparing elements](#comparing-elements)
- [Adding a child element](#adding-a-child-element)
- [Replacing element](#replacing-element)
- [Removing element](#removing-element)
- [Working with cache](#working-with-cache)
- [Miscellaneous](#miscellaneous)
- [Comparison with other parsers](#comparison-with-other-parsers)

## Installation

To install DiDOM run the command:

    composer require imangazaliev/didom

## Quick start

```php
use DiDom\Document;

$document = new Document('http://www.news.com/', true);

$posts = $document->find('.post');

foreach($posts as $post) {
    echo $post->text(), "\n";
}
```

## Creating new document

DiDom allows to load HTML in several ways:

##### With constructor

```php
// the first parameter is a string with HTML
$document = new Document($html);

// file path
$document = new Document('page.html', true);

// or URL
$document = new Document('http://www.example.com/', true);
```

The second parameter specifies if you need to load file. Default is `false`.

##### With separate methods

```php
$document = new Document();

$document->loadHtml($html);

$document->loadHtmlFile('page.html');

$document->loadHtmlFile('http://www.example.com/');
```

There are two methods available for loading XML: `loadXml` and `loadXmlFile`.

These methods accept additional options:

```php
$document->loadHtml($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
```

## Search for elements

DiDOM accepts CSS selector or XPath as an expression for search. You need to path expression as the first parameter, and specify its type in the second one (default type is `Query::TYPE_CSS`):

##### With method `find()`:

```php
use DiDom\Document;
use DiDom\Query;

...

// CSS selector
$posts = $document->find('.post');

// XPath
$posts = $document->find("//div[contains(@class, 'post')]", Query::TYPE_XPATH);
```

If the elements that match a given expression are found, then method returns an array of instances of `DiDom\Element`, otherwise - an empty array. You could also get an array of `DOMElement` objects. To get this, pass `false` as the third parameter.

##### With magic method `__invoke()`:

```php
$posts = $document('.post');
```

##### With method `xpath()`:

```php
$posts = $document->xpath("//*[contains(concat(' ', normalize-space(@class), ' '), ' post ')]");
```

You can do search inside an element:

```php
echo $document->find('nav')[0]->first('ul.menu')->xpath('//li')[0]->text();
```

### Verify if element exists

To verify if element exist use `has()` method:

```php
if ($document->has('.post')) {
    // code
}
```

If you need to check if element exist and then get it:

```php
if ($document->has('.post')) {
    $elements = $document->find('.post');
    // code
}
```

but it would be faster like this:

```php
if (count($elements = $document->find('.post')) > 0) {
    // code
}
```

because in the first case it makes two requests.

## Supported selectors

DiDom supports search by:

- tag
- class, ID, name and value of an attribute
- pseudo-classes:
    - first-, last-, nth-child
    - empty and not-empty
    - contains
    - has

```php
// all links
$document->find('a');

// any element with id = "foo" and "bar" class
$document->find('#foo.bar');

// any element with attribute "name"
$document->find('[name]');
// the same as
$document->find('*[name]');

// input field with the name "foo"
$document->find('input[name=foo]');
$document->find('input[name=\'bar\']');
$document->find('input[name="baz"]');

// any element that has an attribute starting with "data-" and the value "foo"
$document->find('*[^data-=foo]');

// all links starting with https
$document->find('a[href^=https]');

// all images with the extension png
$document->find('img[src$=png]');

// all links containing the string "example.com"
$document->find('a[href*=example.com]');

// text of the links with "foo" class
$document->find('a.foo::text');

// address and title of all the fields with "bar" class
$document->find('a.bar::attr(href|title)');
```

## Output

### Getting HTML

##### With method `html()`:

```php
$posts = $document->find('.post');

echo $posts[0]->html();
```

##### Casting to string:

```php
$html = (string) $posts[0];
```

##### Formatting HTML output

```php
$html = $document->format()->html();
```

An element does not have `format()` method, so if you need to output formatted HTML of the element, then first you have to convert it to a document:


```php
$html = $element->toDocument()->format()->html();
```

#### Inner HTML

```php
$innerHtml = $element->innerHtml();
```

Document does not have the method `innerHtml()`, therefore, if you need to get inner HTML of a document, convert it into an element first:

```php
$innerHtml = $document->toElement()->innerHtml();
```

### Getting XML

```php
echo $document->xml();

echo $document->first('book')->xml();
```

### Getting content

```php
$posts = $document->find('.post');

echo $posts[0]->text();
```

## Creating a new element

### Creating an instance of the class

```php
use DiDom\Element;

$element = new Element('span', 'Hello');

// Outputs "<span>Hello</span>"
echo $element->html();
```

First parameter is a name of an attribute, the second one is its value (optional), the third one is element attributes (optional).

An example of creating an element with attributes:

```php
$attributes = ['name' => 'description', 'placeholder' => 'Enter description of item'];

$element = new Element('textarea', 'Text', $attributes);
```

An element can be created from an instance of the class `DOMElement`:

```php
use DiDom\Element;
use DOMElement;

$domElement = new DOMElement('span', 'Hello');

$element = new Element($domElement);
```

### Using the method `createElement`

```php
$document = new Document($html);

$element = $document->createElement('span', 'Hello');
```

## Getting parent element

```php
$document = new Document($html);

$input = $document->find('input[name=email]')[0];

var_dump($input->parent());
```

## Getting sibling elements

```php
$document = new Document($html);

$item = $document->find('ul.menu > li')[1];

var_dump($item->previousSibling());

var_dump($item->nextSibling());
```

## Getting the child elements

```php
$html = '<div>Foo<span>Bar</span><!--Baz--></div>';

$document = new Document($html);

$div = $document->first('div');

// element node (DOMElement)
// string(3) "Bar"
var_dump($div->child(1)->text());

// text node (DOMText)
// string(3) "Foo"
var_dump($div->firstChild()->text());

// comment node (DOMComment)
// string(3) "Baz"
var_dump($div->lastChild()->text());

// array(3) { ... }
var_dump($div->children());
```

## Getting document

```php
$document = new Document($html);

$element = $document->find('input[name=email]')[0];

$document2 = $element->getDocument();

// bool(true)
var_dump($document->is($document2));
```

## Working with element attributes

#### Getting attribute name
```php
$name = $element->tag;
```

#### Creating/updating an attribute

##### With method `setAttribute`:
```php
$element->setAttribute('name', 'username');
```

##### With method `attr`:
```php
$element->attr('name', 'username');
```

##### With magic method `__set`:
```php
$element->name = 'username';
```

#### Getting value of an attribute

##### With method `getAttribute`:
```php
$username = $element->getAttribute('value');
```

##### With method `attr`:
```php
$username = $element->attr('value');
```

##### With magic method `__get`:
```php
$username = $element->name;
```

Returns `null` if attribute is not found.

#### Verify if attribute exists

##### With method `hasAttribute`:
```php
if ($element->hasAttribute('name')) {
    // code
}
```

##### With magic method `__isset`:
```php
if (isset($element->name)) {
    // code
}
```

#### Removing attribute:

##### With method `removeAttribute`:
```php
$element->removeAttribute('name');
```

##### With magic method `__unset`:
```php
unset($element->name);
```

## Comparing elements

```php
$element  = new Element('span', 'hello');
$element2 = new Element('span', 'hello');

// bool(true)
var_dump($element->is($element));

// bool(false)
var_dump($element->is($element2));
```

## Appending child elements

```php
$list = new Element('ul');

$item = new Element('li', 'Item 1');

$list->appendChild($item);

$items = [
    new Element('li', 'Item 2'),
    new Element('li', 'Item 3'),
];

$list->appendChild($items);
```

## Adding a child element

```php
$list = new Element('ul');

$item = new Element('li', 'Item 1');
$items = [
    new Element('li', 'Item 2'),
    new Element('li', 'Item 3'),
];

$list->appendChild($item);
$list->appendChild($items);
```

## Replacing element

```php
$element = new Element('span', 'hello');

$document->find('.post')[0]->replace($element);
```

## Removing element

```php
$document->find('.post')[0]->remove();
```

## Working with cache
Cache is an array of XPath expressions, that were converted from CSS.
#### Getting from cache
```php
use DiDom\Query;

...

$xpath    = Query::compile('h2');
$compiled = Query::getCompiled();

// array('h2' => '//h2')
var_dump($compiled);
```
#### Installing cache
```php
Query::setCompiled(['h2' => '//h2']);
```

## Miscellaneous

#### `preserveWhiteSpace`

By default, whitespace preserving is disabled.

You can enable the `preserveWhiteSpace` option before loading the document:

```php
$document = new Document();

$document->preserveWhiteSpace();

$document->loadXml($xml);
```

#### `count`

The `count ()` method counts children that match the selector:

```php
// print the number of links in the document
echo $document->count('a');
```

#### `matches`

Returns `true` if the node matches the selector:

```php
$element->matches('div#content');

// strict match
// returns true if the element is a div with id equals content and nothing else
// if the element has any other attributes the method returns false
$element->matches('div#content', true);
```

#### `isTextNode`

Checks whether an element is a text node (DOMText):

```php
$element->isTextNode();
```

#### `isCommentNode`

Checks whether the element is a comment (DOMComment):

```php
$element->isCommentNode();
```

## Comparison with other parsers

[Comparison with other parsers](https://github.com/Imangazaliev/DiDOM/wiki/Comparison-with-other-parsers-(1.0))
