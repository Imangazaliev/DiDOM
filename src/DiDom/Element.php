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
    protected $node;

    /**
     * Constructor.
     * 
     * @param  \DOMElement|string $name
     * @param  string $value
     * @param  array  $attributes
     * @return void
     * @throws \InvalidArgumentException
     */
    public function __construct($name, $value = '', $attributes = [])
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $node = is_string($name) ? $document->createElement($name, $value) : $name;

        $this->setNode($node);

        if (!is_array($attributes)) {
            throw new InvalidArgumentException(sprintf('%s expects parameter 3 to be array, %s given', __METHOD__, (is_object($attributes) ? get_class($attributes) : gettype($attributes))));
        }

        foreach ($attributes as $name => $value) {
            $this->setAttribute($name, $value);
        }
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
     * Searches for the element in the DOM tree.
     * 
     * @param  string $expression XPath expression or CSS selector
     * @param  bool   $wrapElement returns \DiDom\Element if true, otherwise \DOMElement
     * @param  string $type the type of the expression
     * @return \DiDom\Element[]|\DOMElement[]
     */
    public function find($expression, $type = Query::TYPE_CSS, $wrapElement = true)
    {
        return $this->toDocument()->find($expression, $type, $wrapElement);
    }

    /**
     * @param  string $expression XPath expression
     * @param  bool   $wrapElement returns \DiDom\Element if true, otherwise \DOMElement
     * @return \DiDom\Element[]|\DOMElement[]
     */
    public function xpath($expression, $wrapElement = true)
    {
        return $this->find($expression, Query::TYPE_XPATH, $wrapElement);
    }

    /**
     * Determine if an attribute exists on the element.
     *
     * @param  string $name
     * @return bool
     */
    public function hasAttribute($name)
    {
        return $this->node->hasAttribute($name);
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
        $this->node->setAttribute($name, $value);

        return $this;
    }

    /**
     * Access to the element's attributes.
     *
     * @param  string $name
     * @param  string $default
     * @return string|null The value of the attribute or null if attribute does not exist
     */
    public function getAttribute($name, $default = null)
    {
        if ($this->hasAttribute($name)) {
            return $this->node->getAttribute($name);
        }

        return $default;
    }

    /**
     * Unset an attribute on the element.
     *
     * @param  string $name
     * @return \DiDom\Element
     */
    public function removeAttribute($name)
    {
        $this->node->removeAttribute($name);

        return $this;
    }

    /**
     * Alias for getAttribute and setAttribute methods.
     *
     * @param  string $name
     * @param  string $value
     * @return string|null|\DiDom\Element
     */
    public function attr($name, $value = null)
    {
        if ($value === null) {
            return $this->getAttribute($name);
        }

        return $this->setAttribute($name, $value);
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
     * Get the text content of this node and its descendants.
     * 
     * @return string
     */
    public function text()
    {
        return $this->node->textContent;
    }

    /**
     * Set the value of this node.
     *
     * @param  string $value
     * @return \DiDom\Element
     */
    public function setValue($value)
    {
        if (!is_string($value)) {
            throw new InvalidArgumentException(sprintf('%s expects parameter 1 to be string, %s given', __METHOD__, (is_object($value) ? get_class($value) : gettype($value))));
        }

        $this->node->nodeValue = $value;

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
            $element = $element->getNode();
        }

        if (!$element instanceof \DOMNode) {
            throw new InvalidArgumentException(sprintf('Argument 1 passed to %s must be an instance of %s or DOMNode, %s given', __METHOD__, __CLASS__, (is_object($element) ? get_class($element) : gettype($element))));
        }

        return $this->node->isSameNode($element);
    }

    /**
     * @return \DiDom\Document
     */
    public function parent()
    {
        return new Document($this->getNode()->ownerDocument);
    }

    /**
     * Sets current \DOMElement instance.
     *
     * @param  \DOMElement $node
     * @return \DiDom\Element
     */
    protected function setNode(\DOMElement $node)
    {
        $this->node = $node;

        return $this;
    }

    /**
     * @return \DOMElement
     */
    public function getNode()
    {
        return $this->node;
    }

    /**
     * Get the DOM document with the current element.
     * 
     * @return \DiDom\Document
     */
    public function toDocument()
    {
        $document = new Document();
        $document->appendChild($this->node);

        return $document;
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
     * @return string|null
     */
    public function __get($name)
    {
        switch ($name) {
            case 'tag':
                return $this->node->tagName;
                break;
            default:
                return $this->getAttribute($name);
        }
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
     * @param  bool   $wrapElement returns \DiDom\Element if true, otherwise \DOMElement
     * @return \DiDom\Element[]|\DOMElement[]
     */
    public function __invoke($expression, $type = Query::TYPE_CSS, $wrapElement = true)
    {
        return $this->find($expression, $type, $wrapElement);
    }
}
