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
- [Поддерживамые селекторы](#Поддерживамые-селекторы)
- [Output](#output)
- [Creating a new element](#creating-a-new-element)
- [Getting parent element](#getting-parent-element)
- [Working with element attributes](#working-with-element-attributes)
- [Comparing elements](#comparing-elements)
- [Replacing element](#replacing-element)
- [Removing element](#removing-element)
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

Для загрузки XML есть соответствующие методы `loadXml` и `loadXmlFile`.

При загрузке через эти методы документу можно передать дополнительные параметры:

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

If the elements that match a given expression are found, then method returns an array of instances of `DiDom\Element`, otherwise - an empty array. You could also get an array of `DOMElement` objects. To get this, pass `false` as the third parameter.

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
if (count($elements = $document->find('.post')) != 0) {
    // code
}
```

because in the first case it makes two requests.

## Поддерживамые селекторы

DiDom поддерживает поиск по:

- тэгу
- классу, идентификатору, имени и значению атрибута
- псевдоклассам:
    - first-, last-, nth-child
    - empty и not-empty
    - contains

```php
// все ссылки
$document->find('a');

// любой элемент с id = "foo" и классом "bar"
$document->find('#foo.bar');

// любой элемент, у которого есть атрибут "name"
$document->find('[name]');
// эквивалентно
$document->find('*[name]');

// поле ввода с именем "foo"
$document->find('input[name=foo]');
$document->find('input[name=\'bar\']');
$document->find('input[name="baz"]');

// любой элемент, у которого есть атрибут,
// начинающийся с "data-" и равный "foo"
$document->find('*[^data-=foo]');

// все ссылки, у которых адрес начинается с https
$document->find('a[href^=https]');

// все изображения с расширением png
$document->find('img[src$=png]');

// все ссылки, содержащие в своем адресе строку "exapmle.com"
$document->find('a[href*=exapmle.com]');

// текст всех ссылок с классом "foo"
$document->find('a.foo::text');

// адрес и текст подсказки всех полей с классом "bar"
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

#### Внутренний HTML

```php
$innerHtml = $element->innerHtml();
```

Метод `innerHtml()` отсутствует у документа, поэтому, если нужно получить внутренний HTML-код документа, необходимо сначала преобразовать его в элемент:

```php
$innerHtml = $document->toElement()->innerHtml();
```

#### Дополнительные параметры

```php
$html = $document->format()->html(LIBXML_NOEMPTYTAG);
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
$element    = new Element($domElement);
```

### Using the method `createElement`

```php
$document = new Document($html);
$element  = $document->createElement('span', 'Hello');
```

## Getting parent element

```php
$document = new Document($html);
$element  = $document->find('input[name=email]')[0];

// getting parent
$parent = $element->parent();

// bool(true)
var_dump($document->is($parent));
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

## Добавление дочерних элементов

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

## Comparison with other parsers

[Comparison with other parsers](https://github.com/Imangazaliev/DiDOM/wiki/Comparison-with-other-parsers-(1.6.3))
