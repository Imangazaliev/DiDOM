# DiDOM

[![Build Status](https://codeship.com/projects/cf938980-36f0-0134-119e-36dc468776c7/status?branch=master)](https://codeship.com/projects/165662)
[![Total Downloads](https://poser.pugx.org/imangazaliev/didom/downloads)](https://packagist.org/packages/imangazaliev/didom)
[![Latest Stable Version](https://poser.pugx.org/imangazaliev/didom/v/stable)](https://packagist.org/packages/imangazaliev/didom)
[![License](https://poser.pugx.org/imangazaliev/didom/license)](https://packagist.org/packages/imangazaliev/didom)

[English version](README.md)

DiDOM - простая и быстрая библиотека для парсинга HTML.

## Содержание

- [Установка](#Установка)
- [Быстрый старт](#Быстрый-старт)
- [Создание нового документа](#Создание-нового-документа)
- [Поиск элементов](#Поиск-элементов)
- [Проверка наличия элемента](#Проверка-наличия-элемента)
- [Поддерживамые селекторы](#Поддерживамые-селекторы)
- [Изменение содержимого](#Изменение-содержимого)
- [Вывод содержимого](#Вывод-содержимого)
- [Создание нового элемента](#Создание-нового-элемента)
- [Получение родительского элемента](#Получение-родительского-элемента)
- [Получение соседних элементов](#Получение-соседних-элементов)
- [Получение дочерних элементов](#Получение-соседних-элементов)
- [Получение документа](#Получение-документа)
- [Работа с атрибутами элемента](#Работа-с-атрибутами-элемента)
- [Сравнение элементов](#Сравнение-элементов)
- [Добавление дочерних элементов](#Добавление-дочерних-элементов)
- [Замена элемента](#Замена-элемента)
- [Удаление элемента](#Удаление-элемента)
- [Работа с кэшем](#Работа-с-кэшем)
- [Прочее](#Прочее)
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

В качестве выражения для поиска можно передать CSS-селектор или XPath. Для этого в первом параметре нужно передать само выражение, а во втором - его тип (по умолчанию - `Query::TYPE_CSS`):

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

Если элементы, соответствующие заданному выражению, найдены, метод вернет массив с экземплярами класса `DiDom\Element`, иначе - пустой массив. При желании можно получить массив объектов `DOMElement`. Для этого необходимо передать в качестве третьего параметра `false`.

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
echo $document->find('nav')[0]->first('ul.menu')->xpath('//li')[0]->text();
```

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
if (count($elements = $document->find('.post')) > 0) {
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
    - has

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
$document->find('input[name=\'foo\']');
$document->find('input[name="foo"]');

// поле ввода, название которого НЕ равно "foo"
$document->find('input[name!="foo"]');

// любой элемент, у которого есть атрибут,
// начинающийся с "data-" и равный "foo"
$document->find('*[^data-=foo]');

// все ссылки, у которых адрес начинается с https
$document->find('a[href^=https]');

// все изображения с расширением png
$document->find('img[src$=png]');

// все ссылки, содержащие в своем адресе строку "example.com"
$document->find('a[href*=example.com]');

// все ссылки, содержащие в атрибуте data-foo значение bar отделенное пробелом
$document->find('a[data-foo~=bar]');

// текст всех ссылок с классом "foo"
$document->find('a.foo::text');

// адрес и текст подсказки всех полей с классом "bar"
$document->find('a.bar::attr(href|title)');

// все ссылки, которые являются прямыми потомками текущего элемента
$element->find('> a');
```

## Изменение содержимого

### Изменение HTML

```php
$element->setInnerHtml('<a href="#">Foo</a>');
```

### Изменение значения

```php
$element->setValue('Foo');
```

## Вывод содержимого

### Получение HTML

##### Через метод `html()`:

```php
echo $document->html();

echo $document->first('.post')->html();
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

### Получение XML

```php
echo $document->xml();

echo $document->first('book')->xml();
```

### Получение содержимого

```php
echo $element->text();
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

### С помощью CSS-селектора

```php
$document = new Document($html);

$element = $document->createElementBySelector('div.block', 'Foo', ['id' => '#content']);
```

Можно так же использовать статический метод `createBySelector` класса `Element`:

```php
$element = Element::createBySelector('div.block', 'Foo', ['id' => '#content']);
```

## Получение родительского элемента

```php
$element->parent();
```

Так же можно получить родительский элемент, соответствующий селектору:

```php
$element->closest('.foo');
```

Вернет родительский элемент, у которого есть класс `foo`. Если подходящий элемент не найден, метод вернет `null`.

## Получение соседних элементов

Первый аргумент - CSS-селектор, второй - тип узла (`DOMElement`, `DOMText` или `DOMComment`).

Если оба аргумента опущены, будет осуществлен поиск узлов любого типа.

Если селектор указан, а тип узла нет, будет использован тип `DOMElement`.

**Внимание:** Селектор можно использовать только с типом `DOMElement`.


```php
// предыдущий элемент
$item->previousSibling();

// предыдущий элемент, соответствующий селектору
$item->previousSibling('span');

// предыдущий элемент типа DOMElement
$item->previousSibling(null, 'DOMElement');

// предыдущий элемент типа DOMComment
$item->previousSibling(null, 'DOMComment');
```

```php
// все предыдущие элементы
$item->previousSiblings();

// все предыдущие элементы, соответствующие селектору
$item->previousSiblings('span');

// все предыдущие элементы типа DOMElement
$item->previousSiblings(null, 'DOMElement');

// все предыдущие элементы типа DOMComment
$item->previousSiblings(null, 'DOMComment');
```

```php
// следующий элемент
$item->nextSibling();

// следующий элемент, соответствующий селектору
$item->nextSibling('span');

// следующий элемент типа DOMElement
$item->nextSibling(null, 'DOMElement');

// следующий элемент типа DOMComment
$item->nextSibling(null, 'DOMComment');
```

```php
// все последующие элементы
$item->nextSiblings();

// все последующие элементы, соответствующие селектору
$item->nextSiblings('span');

// все последующие элементы типа DOMElement
$item->nextSiblings(null, 'DOMElement');

// все последующие элементы типа DOMComment
$item->nextSiblings(null, 'DOMComment');
```

## Получение дочерних элементов

```php
$html = '<div>Foo<span>Bar</span><!--Baz--></div>';

$document = new Document($html);

$div = $document->first('div');

// элемент (DOMElement)
// string(3) "Bar"
var_dump($div->child(1)->text());

// текстовый узел (DOMText)
// string(3) "Foo"
var_dump($div->firstChild()->text());

// комментарий (DOMComment)
// string(3) "Baz"
var_dump($div->lastChild()->text());

// array(3) { ... }
var_dump($div->children());
``

## Получение документа

```php
$document = new Document($html);

$element  = $document->first('input[name=email]');

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

#### Получение всех атрибутов:

```php
var_dump($element->attributes());
```

#### Получение определенных атрибутов:

```php
var_dump($element->attributes(['name', 'type']));
```

#### Удаление всех атрибутов:

```php
$element->removeAllAttributes();
```

#### Удаление всех атрибутов, за исключением указанных:

```php
$element->removeAllAttributes(['name', 'type']);
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

$list->appendChild($item);

$items = [
    new Element('li', 'Item 2'),
    new Element('li', 'Item 3'),
];

$list->appendChild($items);
```

## Замена элемента

```php
$title = new Element('title', 'foo');

$document->first('title')->replace($title);
```

**Внимание:** заменить можно только те элементы, которые были найдены непосредственно в документе:

```php
// ничего не выйдет
$document->first('head')->first('title')->replace($title);

// а вот так да
$document->first('head title')->replace($title);
```

## Удаление элемента

```php
$document->first('title')->remove();
```

**Внимание:** удалить можно только те элементы, которые были найдены непосредственно в документе:

```php
// ничего не выйдет
$document->first('head')->first('title')->remove();

// а вот так да
$document->first('head title')->remove();
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

## Прочее

#### `preserveWhiteSpace`

По умолчанию сохранение пробелов между тегами отключено.

Включать опцию `preserveWhiteSpace` следует до загрузки документа:

```php
$document = new Document();

$document->preserveWhiteSpace();

$document->loadXml($xml);
```

#### `count`

Метод `count()` позволяет подсчитать количество дочерних элементов, соотвествующих селектору:

```php
// выведет количество ссылок в документе
echo $document->count('a');
```

#### `matches`

Возвращает `true`, если узел подходит под селектор:

```php
// вернет true, если элемент это div с идентификатором content
$element->matches('div#content');

// строгое соответствие
// вернет true, если элемент это div с идентификатором content и ничего более
// если у элемента будут какие-либо другие атрибуты, метод вернет false
$element->matches('div#content', true);
```

#### `isElementNode`

Проверяет, является ли элемент узлом типа DOMElement:

```php
$element->isElementNode();
```

#### `isTextNode`

Проверяет, является ли элемент текстовым узлом (DOMText):

```php
$element->isTextNode();
```

#### `isCommentNode`

Проверяет, является ли элемент комментарием (DOMComment):

```php
$element->isCommentNode();
```

## Сравнение с другими парсерами

[Сравнение с другими парсерами](https://github.com/Imangazaliev/DiDOM/wiki/Сравнение-с-другими-парсерами-(1.6.3))
