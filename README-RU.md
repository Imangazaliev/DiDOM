# DiDOM

[![Build Status](https://travis-ci.org/Imangazaliev/DiDOM.svg)](https://travis-ci.org/Imangazaliev/DiDOM)
[![Total Downloads](https://poser.pugx.org/imangazaliev/didom/downloads)](https://packagist.org/packages/imangazaliev/didom)
[![Latest Stable Version](https://poser.pugx.org/imangazaliev/didom/v/stable)](https://packagist.org/packages/imangazaliev/didom)
[![License](https://poser.pugx.org/imangazaliev/didom/license)](https://packagist.org/packages/imangazaliev/didom)

DiDOM - простая и быстрая библиотека для парсинга HTML.

## Содержание

- [Установка](#Установка)
- [Быстрый старт](#Быстрый-старт)
- [Создание нового документа](#Создание-нового-документа)
- [Поиск элементов](#Поиск-элементов)
- [Проверка наличия элемента](#Быстрый-старт)
- [Вывод содержимого](#Вывод-содержимого)
- [Создание нового элемента](#Создание-нового-элемента)
- [Получение родителя](#Получение-родителя)
- [Работа с атрибутами элемента](#Работа-с-атрибутами-элемента)
- [Сравнение элементов](#Сравнение-элементов)
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

## Поиск элементов

В качестве выражения для поиска можно передать CSS-селектор или XPath-путь. Для этого в первом параметре нужно передать само выражение, а во втором - его тип (по умолчанию - `Query::TYPE_CSS`):

##### Через метод `find()`:

```php
use DiDom\Document;
use DiDom\Query;
    
...

// CSS-селектор    
$posts = $document->find('.post');

// XPath-путь
$posts = $document->find("//div[contains(@class, 'post')]", Query::TYPE_XPATH);
```

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

Метод `format()` отсутствует у элемента, поэтому, если нужно вывести отформатированный HTML-код элемента, необходимо сначала преобразовать его в документ:


```php
$html = $element->toDocument()->format()->html();
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
$element    = new Element($domElement);
```

### С помощью метода `createElement`

```php
$document = new Document($html);
$element  = $document->createElement('span', 'Hello');
```

## Получение родителя

```php
$document = new Document($html);
$element  = $document->find('input[name=email]')[0];

// получение родителя
$parent = $element->parent();

// bool(true)
var_dump($document->is($parent));
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

[Сравнение с другими парсерами](https://github.com/Imangazaliev/DiDOM/wiki/Сравнение-с-другими-парсерами)
