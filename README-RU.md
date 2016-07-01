# DiDOM

[![Build Status](https://travis-ci.org/Imangazaliev/DiDOM.svg)](https://travis-ci.org/Imangazaliev/DiDOM)
[![Total Downloads](https://poser.pugx.org/imangazaliev/didom/downloads)](https://packagist.org/packages/imangazaliev/didom)
[![Latest Stable Version](https://poser.pugx.org/imangazaliev/didom/v/stable)](https://packagist.org/packages/imangazaliev/didom)
[![License](https://poser.pugx.org/imangazaliev/didom/license)](https://packagist.org/packages/imangazaliev/didom)

[README на английском](README.md)

DiDOM - простая и быстрая библиотека для парсинга HTML.

## Содержание

- [Установка](#Установка)
- [Быстрый старт](#Быстрый-старт)
- [Создание нового документа](#Создание-нового-документа)
- [Поиск элементов](#Поиск-элементов)
- [Проверка наличия элемента](#Проверка-наличия-элемента)
- [Поддерживамые селекторы](#Поддерживамые-селекторы)
- [Вывод содержимого](#Вывод-содержимого)
- [Создание нового элемента](#Создание-нового-элемента)
- [Получение родительского элемента](#Получение-родительского-элемента)
- [Получение соседних элементов](#Получение-соседних-элементов)
- [Получение дочерних элементов](#Получение-соседних-элементов)
- [Получение документа](#Получение-документа)
- [Работа с атрибутами элемента](#Работа-с-атрибутами-элемента)
- [Сравнение элементов](#Сравнение-элементов)
- [Добавление дочерних элементов](#Добавление дочерних элементов)
- [Замена элемента](#Замена-элемента)
- [Удаление элемента](#Удаление-элемента)
- [Работа с кэшем](#Работа-с-кэшем)
- [Сравнение с другими парсерами](#Сравнение-с-другими-парсерами)

## Установка

Для установки DiDOM выполните команду:

    composer require imangazaliev/didom

## Быстрый старт

```php    
use DiDom\Document;

$document = new Document('http://www.news.com/', true);

$posts = $document->find('.post');

foreach($posts as $post) {
    echo $post->text(), "\n";
}
```

## Создание нового документа

DiDom позволяет загрузить HTML несколькими способами:

##### Через конструктор

```php    
// в первом параметре передается строка с HTML
$document = new Document($html);

// путь к файлу
$document = new Document('page.html', true);

// или URL
$document = new Document('http://www.example.com/', true);
```

Второй параметр указывает на то, что загружается файл. По умолчанию - `false`.

##### Через отдельные методы

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

## Поиск элементов

В качестве выражения для поиска можно передать CSS-селектор или XPath-путь. Для этого в первом параметре нужно передать само выражение, а во втором - его тип (по умолчанию - `Query::TYPE_CSS`):

##### Через метод `find()`:

```php
use DiDom\Document;
use DiDom\Query;
    
...

// CSS-селектор    
$posts = $document->find('.post');

// XPath-выражение
$posts = $document->find("//div[contains(@class, 'post')]", Query::TYPE_XPATH);
```

##### Через метод `first()`:

Возвращает первый найденный элемент, либо `null`, если элементы, подходящие под селектор не были найдены.

##### Через магический метод `__invoke()`:

```php
$posts = $document('.post');
```

##### Через метод `xpath()`:

```php
$posts = $document->xpath("//*[contains(concat(' ', normalize-space(@class), ' '), ' post ')]");
```

Можно осуществлять поиск и внутри элемента:

```php
echo $document->find('.post')[0]->find('h2')[0]->text();
```

Если элементы, соответствующие заданному выражению, найдены, метод вернет массив с экземплярами класса `DiDom\Element`, иначе - пустой массив. При желании можно получить массив объектов `DOMElement`. Для этого необходимо передать в качестве третьего параметра `false`.

### Проверка наличия элемента

Проверить наличие элемента можно с помощью метода `has()`:

```php
if ($document->has('.post')) {
    // код
}
```

Если нужно проверить наличие элемента, а затем получить его, то можно сделать так:

```php
if ($document->has('.post')) {
    $elements = $document->find('.post');
    // код
}
```

но быстрее так:

```php
if (count($elements = $document->find('.post')) != 0) {
    // код
}
```

т.к. в первом случае выполняется два запроса.

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

// все ссылки, содержащие в своем адресе строку "example.com"
$document->find('a[href*=example.com]');

// текст всех ссылок с классом "foo"
$document->find('a.foo::text');

// адрес и текст подсказки всех полей с классом "bar"
$document->find('a.bar::attr(href|title)');
```

## Вывод содержимого

### Получение HTML

##### Через метод `html()`:

```php    
$posts = $document->find('.post');

echo $posts[0]->html();
```

##### Приведение к строке:

```php
$html = (string) $posts[0];
```

##### Форматирование HTML при выводе

```php
$html = $document->format()->html();
```

Метод `format()` отсутствует у элемента, поэтому, если нужно получить отформатированный HTML-код элемента, необходимо сначала преобразовать его в документ:


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

### Получение содержимого

```php    
$posts = $document->find('.post');

echo $posts[0]->text();
```

## Создание нового элемента

### Создание экземпляра класса

```php
use DiDom\Element;

$element = new Element('span', 'Hello');
    
// выведет "<span>Hello</span>"
echo $element->html();
```

Первым параметром передается название элемента, вторым - его значение (необязательно), третьим - атрибуты элемента (необязательно).

Пример создания элемента с атрибутами:

```php
$attributes = ['name' => 'description', 'placeholder' => 'Enter description of item'];

$element = new Element('textarea', 'Text', $attributes);
```

Элемент можно создать и из экземпляра класса `DOMElement`:

```php
use DiDom\Element;
use DOMElement;

$domElement = new DOMElement('span', 'Hello');
$element = new Element($domElement);
```

### С помощью метода `createElement`

```php
$document = new Document($html);
$element = $document->createElement('span', 'Hello');
```

## Получение родительского элемента

```php
$document = new Document($html);
$input = $document->find('input[name=email]')[0];

var_dump($input->parent());
```

## Получение соседних элементов

```php
$document = new Document($html);
$item = $document->find('ul.menu > li')[1];

// предыдущий элемент
var_dump($item->previousSibling());

// следующий элемент
var_dump($item->nextSibling());
```

## Получение дочерних элементов

```php
$html = '
<ul>
    <li>Foo</li>
    <li>Bar</li>
    <li>Baz</li>
</ul>
';

$document = new Document($html);
$list = $document->first('ul');

// string(3) "Baz"
var_dump($item->child(2)->text());

// string(3) "Foo"
var_dump($item->firstChild()->text());

// string(3) "Baz"
var_dump($item->lastChild()->text());

// array(3) { ... }
var_dump($item->children());
```

## Получение документа

```php
$document = new Document($html);
$element  = $document->find('input[name=email]')[0];

$document2 = $element->getDocument();

// bool(true)
var_dump($document->is($document2));
```

## Работа с атрибутами элемента

#### Получение названия элемента
```php
$name = $element->tag;
```

#### Создание/изменение атрибута

##### Через метод `setAttribute`:
```php
$element->setAttribute('name', 'username');
```

##### Через метод `attr`:
```php
$element->attr('name', 'username');
```

##### Через магический метод `__set`:
```php
$element->name = 'username';
```

#### Получение значения атрибута

##### Через метод `getAttribute`:
```php
$username = $element->getAttribute('value');
```

##### Через метод `attr`:
```php
$username = $element->attr('value');
```

##### Через магический метод `__get`:
```php
$username = $element->name;
```

Если атрибут не найден, вернет `null`.

#### Проверка наличия атрибута

##### Через метод `hasAttribute`:
```php
if ($element->hasAttribute('name')) {
    // код
}
```

##### Через магический метод `__isset`:
```php
if (isset($element->name)) {
    // код
}
```

#### Удаление атрибута:

##### Через метод `removeAttribute`:
```php
$element->removeAttribute('name');
```

##### Через магический метод `__unset`:
```php
unset($element->name);
```

## Сравнение элементов

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

## Замена элемента

```php
$element = new Element('span', 'hello');

$document->find('.post')[0]->replace($element);
```

## Удаление элемента

```php
$document->find('.post')[0]->remove();
```

## Работа с кэшем

Кэш - массив XPath-выражений, полученных из CSS.

#### Получение кэша

```php
use DiDom\Query;
    
...

$xpath    = Query::compile('h2');
$compiled = Query::getCompiled();

// array('h2' => '//h2')
var_dump($compiled);
```

#### Установка кэша

```php
Query::setCompiled(['h2' => '//h2']);
```

## Сравнение с другими парсерами

[Сравнение с другими парсерами](https://github.com/Imangazaliev/DiDOM/wiki/Сравнение-с-другими-парсерами-(1.6.3))
