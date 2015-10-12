<?php

namespace DiDom;

use DOMDocument;
use DOMElement;
use InvalidArgumentException;

class Element
{
    /**
     * The DOM element instance.
     * 
     * @var \DOMElement;
     */
    protected $domElement;

    /**
     * Constructor.
     * 
     * @param  \DOMElement|string $name
     * @param  string $value
     * @param  array  $attributes
     * @return void
     */
    public function __construct($name, $value = '', $attributes = [])
    {
        $document   = new DOMDocument('1.0', 'UTF-8');
        $domElement = ($name instanceof DOMElement) ? $name : $document->createElement($name, $value);

        if (!is_array($attributes)) {
            throw new InvalidArgumentException(sprintf('%s expects parameter 3 to be array, %s given', __METHOD__, gettype($attributes)));
        }

        foreach ($attributes as $name => $value) {
            $domElement->setAttribute($name, $value);
        }

        $this->domElement = $domElement;
    }

    /**
     * Get the text content of this node and its descendants.
     * 
     * @return string
     */
    public function text()
    {
        return $this->domElement->textContent;
    }

    /**
     * Get the DOM document with the current element.
     * 
     * @return \DiDom\Document
     */
    public function toDocument()
    {
        $document = new Document();

        $document->appendChild($this->domElement);

        return $document;
    }

    /**
     * Searches for the element in the DOM tree.
     * 
     * @param  string $expression XPath expression or CSS selector
     * @param  string $type the type of the expression
     * @return \DiDom\Element[] array of Elements
     */
    public function find($expression, $type = Query::TYPE_CSS)
    {
        return $this->toDocument()->find($expression, $type);
    }

    /**
     * Checks for the item.
     * 
     * @param  string $expression XPath expression or CSS selector
     * @param  string $type the type of the expression
     * @return bool
     */
    public function has($expression, $type = Query::TYPE_CSS)
    {
        return $this->toDocument()->has($expression, $type);
    }

    /**
     * Dumps the internal document into a string using HTML formatting.
     * 
     * @return string
     */
    public function html()
    {
        return $this->toDocument()->html();
    }

    /**
     * Determine if an attribute exists on the element.
     *
     * @param  string $name
     * @return bool
     */
    public function hasAttribute($name)
    {
        return $this->domElement->hasAttribute($name);
    }

    /**
     * Set an attribute on the element.
     *
     * @param  string $name
     * @param  string $value
     * @return \DiDom\Element
     */
    public function setAttribute($name, $value)
    {
        $this->domElement->setAttribute($name, $value);

        return $this;
    }

    /**
     * Access to the element's attributes.
     *
     * @param  string $name
     * @param  mixed  $default
     * @return mixed
     */
    public function getAttribute($name, $default = null)
    {
        if ($this->hasAttribute($name)) {
            return $this->domElement->getAttribute($name);
        }

        return $default;
    }

    /**
     * Alias for getAttribute and setAttribute methods.
     *
     * @param  string $name
     * @param  string $value
     * @return mixed
     */
    public function attr($name, $value = null)
    {
        if ($value === null) {
            return $this->getAttribute($name);
        }

        return $this->setAttribute($name, $value);
    }

    /**
     * Unset an attribute on the element.
     *
     * @param  string $name
     * @return $this
     */
    public function removeAttribute($name)
    {
        $this->domElement->removeAttribute($name);

        return $this;
    }

    /**
     * @param  Element|\DOMNode $element
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function is($element)
    {
        if ($element instanceof self) {
            $element = $element->getElement();
        }

        if (!$element instanceof \DOMNode) {
            throw new InvalidArgumentException(sprintf('Argument 1 passed to %s must be an instance of %s or %s, %s given', __METHOD__, __CLASS__, 'DOMNode', (is_object($element) ? get_class($element) : gettype($element))));
        }

        return $this->domElement->isSameNode($element);
    }

    /**
     * @return Document
     */
    public function parent()
    {
        return new Document($this->getElement()->ownerDocument);
    }

    /**
     * @return \DOMElement
     */
    public function getElement()
    {
        return $this->domElement;
    }

    /**
     * Determine if an attribute exists on the element.
     *
     * @param  string $name
     * @return bool
     */
    public function __isset($name)
    {
        return $this->hasAttribute($name);
    }

    /**
     * Unset an attribute on the model.
     *
     * @param  string $name
     * @return void
     */
    public function __unset($name)
    {
        $this->removeAttribute($name);
    }

    /**
     * Dynamically set an attribute on the element.
     *
     * @param  string $name
     * @param  mixed  $value
     * @return \DiDom\Element
     */
    public function __set($name, $value)
    {
        return $this->setAttribute($name, $value);
    }

    /**
     * Dynamically access the element's attributes.
     *
     * @param  string $name
     * @return mixed
     */
    public function __get($name)
    {
        switch ($name) {
            case 'tag':
                return $this->domElement->tagName;
                break;
            default:
                return $this->getAttribute($name);
        }
    }

    /**
     * Convert the element to its string representation.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->html();
    }

    /**
     * @param  string $expression
     * @param  string $type
     * @return \DiDom\Element[]
     */
    public function __invoke($expression, $type = Query::TYPE_CSS)
    {
        return $this->find($expression);
    }
}
