# DiDOM

[![Build Status](https://travis-ci.org/Imangazaliev/DiDOM.svg)](https://travis-ci.org/Imangazaliev/DiDOM)
[![Total Downloads](https://poser.pugx.org/imangazaliev/didom/downloads)](https://packagist.org/packages/imangazaliev/didom)
[![Latest Stable Version](https://poser.pugx.org/imangazaliev/didom/v/stable)](https://packagist.org/packages/imangazaliev/didom)
[![License](https://poser.pugx.org/imangazaliev/didom/license)](https://packagist.org/packages/imangazaliev/didom)

[Russian version](README-RU.md)

DiDOM - simple and fast HTML parser.

## Contents

- [Installation](#installation)
- [Quick start](#quick-start)
- [Creating new document](#creating-new-document)
- [Search for elements](#search-for-elements)
- [Verify if element exists](#verify-if-element-exists)
- [Output](#output)
- [Creating a new element](#creating-a-new-element)
- [Working with element attributes](#working-with-element-attributes)
- [Working with cache](#working-with-cache)
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
echo $document->find('.post')[0]->find('h2')[0]->text();
```

If the elements that match a given expression are found, it returns an array of instances of `DiDom\Element`, otherwise - an empty array.

### Verify if element exists

To very if element exist use `has()` method:

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
if (count($elements = $document->find('.post')) != 0) {
    // code
}
```

because in the first case it makes two requests.

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

### Getting content

```php    
$posts = $document->find('.post');

echo $posts[0]->text();
```

## Creating a new element

```php
use DiDom\Element;

$element = new Element('span', 'Hello');
    
// Outputs "<span>Hello</span>"
echo $element->html();
```

First parameter is a name of an attribute, the second one is its value (optional).

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
$element->name = 'username';
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

## Comparison with other parsers

[Comparison with other parsers](https://github.com/Imangazaliev/DiDOM/wiki/Сравнение-с-другими-парсерами)
