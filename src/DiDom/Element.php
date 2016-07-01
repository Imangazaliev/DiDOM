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
     * @param \DOMElement|string $name The tag name of the element
     * @param string $value The value of the element
     * @param array  $attributes The attributes of the element
     *
     * @throws \InvalidArgumentException if the attributes is not an array
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
     * Adds new child at the end of the children.
     * 
     * @param \DiDom\Element|\DOMNode|array $nodes The appended child
     *
     * @return \DiDom\Element
     *
     * @throws \InvalidArgumentException if the provided argument is not an instance of \DOMNode or \DiDom\Element
     */
    public function appendChild($nodes)
    {
        $nodes = is_array($nodes) ? $nodes : [$nodes];

        foreach ($nodes as $node) {
            if ($node instanceof Element) {
                $node = $node->getNode();
            }

            if (!$node instanceof \DOMNode) {
                throw new InvalidArgumentException(sprintf('Argument 1 passed to %s must be an instance of %s\Element or DOMNode, %s given', __METHOD__, __NAMESPACE__, (is_object($node) ? get_class($node) : gettype($node))));
            }

            libxml_use_internal_errors(true);
            libxml_disable_entity_loader(true);

            $cloned = $node->cloneNode(true);
            $newNode = $this->node->ownerDocument->importNode($cloned, true);

            $this->node->appendChild($newNode);

            libxml_clear_errors();

            libxml_disable_entity_loader(false);
            libxml_use_internal_errors(false);
        }

        return $this;
    }

    /**
     * Checks the existence of the item.
     * 
     * @param string $expression XPath expression or CSS selector
     * @param string $type The type of the expression
     *
     * @return bool
     */
    public function has($expression, $type = Query::TYPE_CSS)
    {
        return $this->toDocument()->has($expression, $type);
    }

    /**
     * Searches for an item in the DOM tree for a given XPath expression or a CSS selector.
     * 
     * @param string $expression XPath expression or a CSS selector
     * @param string $type The type of the expression
     * @param bool   $wrapElement Returns array of \DiDom\Element if true, otherwise array of \DOMElement
     *
     * @return \DiDom\Element[]|\DOMElement[]
     */
    public function find($expression, $type = Query::TYPE_CSS, $wrapElement = true)
    {
        return $this->toDocument()->find($expression, $type, $wrapElement);
    }

    /**
     * Searches for an item in the DOM tree and returns first element or null.
     * 
     * @param string $expression XPath expression or a CSS selector
     * @param string $type The type of the expression
     * @param bool   $wrapElement Returns \DiDom\Element if true, otherwise \DOMElement
     *
     * @return \DiDom\Element|\DOMElement|null
     */
    public function first($expression, $type = Query::TYPE_CSS, $wrapElement = true)
    {
        return $this->toDocument()->first($expression, $type, $wrapElement);
    }

    /**
     * Searches for an item in the DOM tree for a given XPath expression.
     * 
     * @param string $expression XPath expression
     * @param bool   $wrapElement Returns array of \DiDom\Element if true, otherwise array of \DOMElement
     *
     * @return \DiDom\Element[]|\DOMElement[]
     */
    public function xpath($expression, $wrapElement = true)
    {
        return $this->find($expression, Query::TYPE_XPATH, $wrapElement);
    }

    /**
     * Determine if an attribute exists on the element.
     *
     * @param string $name The attribute name
     *
     * @return bool
     */
    public function hasAttribute($name)
    {
        return $this->node->hasAttribute($name);
    }

    /**
     * Set an attribute on the element.
     *
     * @param string $name The attribute name
     * @param string $value The attribute value
     *
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
     * @param string $name The attribute name
     * @param string $default The value returned if the attribute does not exist
     *
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
     * @param string $name The attribute name
     *
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
     * @param string $name The attribute name
     * @param string $value The attribute value or null if the attribute does not exist
     *
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
     * Dumps the node into a string using HTML formatting.
     * 
     * @param int $options Additional options
     * 
     * @return string The node HTML
     */
    public function html($options = 0)
    {
        return $this->toDocument()->html($options);
    }

    /**
     * Dumps the node descendants into a string using HTML formatting.
     * 
     * @param int $options Additional options
     * 
     * @return string
     */
    public function innerHtml($options = 0)
    {
        $innerHtml = [];
        $childNodes = $this->node->childNodes;

        foreach ($childNodes as $node) 
        {
            $innerHtml[] = $node->ownerDocument->saveXml($node, $options);
        }

        return implode('', $innerHtml);
    }

    /**
     * Dumps the node into a string using XML formatting.
     * 
     * @param int $options Additional options
     * 
     * @return string The node XML
     */
    public function xml($options = 0)
    {
        return $this->toDocument()->xml($options);
    }

    /**
     * Get the text content of this node and its descendants.
     * 
     * @return string The node value
     */
    public function text()
    {
        return $this->node->textContent;
    }

    /**
     * Set the value of this node.
     *
     * @param string $value The new value of the node
     *
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
     * Indicates if two nodes are the same node.
     * 
     * @param \DiDom\Element|\DOMElement $node
     *
     * @return bool
     *
     * @throws \InvalidArgumentException if the provided argument is not an instance of \DOMElement
     */
    public function is($node)
    {
        if ($node instanceof self) {
            $node = $node->getNode();
        }

        if (!$node instanceof \DOMElement) {
            throw new InvalidArgumentException(sprintf('Argument 1 passed to %s must be an instance of %s or DOMElement, %s given', __METHOD__, __CLASS__, (is_object($node) ? get_class($node) : gettype($node))));
        }

        return $this->node->isSameNode($node);
    }

    /**
     * @return \DiDom\Element|null
     */
    public function parent()
    {
        if ($this->node->parentNode === null) {
            return null;
        }

        return new Element($this->node->parentNode);
    }

    /**
     * @return \DiDom\Element|null
     */
    public function previousSibling()
    {
        if ($this->node->previousSibling === null) {
            return null;
        }

        return new Element($this->node->previousSibling);
    }

    /**
     * @return \DiDom\Element|null
     */
    public function nextSibling()
    {
        if ($this->node->nextSibling === null) {
            return null;
        }

        return new Element($this->node->nextSibling);
    }

    /**
     * @return \DiDom\Element|null
     */
    public function child($index)
    {
        $child = $this->node->childNodes->item($index);

        return $child === null ? null : new Element($child);
    }

    /**
     * @return \DiDom\Element|null
     */
    public function firstChild()
    {
        if ($this->node->firstChild === null) {
            return null;
        }

        return new Element($this->node->firstChild);
    }

    /**
     * @return \DiDom\Element|null
     */
    public function lastChild()
    {
        if ($this->node->lastChild === null) {
            return null;
        }

        return new Element($this->node->lastChild);
    }

    /**
     * @return \DiDom\Element[]
     */
    public function children()
    {
        $children = [];

        foreach ($this->node->childNodes as $node) 
        {
            $children[] = new Element($node);
        }

        return $children;
    }

    /**
     * Removes child from list of children.
     * 
     * @return \DiDom\Element the node that has been removed
     */
    public function remove()
    {
        $node = $this->node->parentNode->removeChild($this->node);

        return new Element($node);
    }

    /**
     * Replaces a child.
     * 
     * @param \DOMElement|\DiDom\Element $newChild The new node
     * @param bool $clone Clone the node if true, otherwise move it
     * 
     * @return \DiDom\Element The node that has been replaced
     */
    public function replace($newNode, $clone = true)
    {
        if ($newNode instanceof self) {
            $newNode = $newNode->getNode();
        }

        if (!$newNode instanceof \DOMElement) {
            throw new InvalidArgumentException(sprintf('Argument 1 passed to %s must be an instance of %s or DOMElement, %s given', __METHOD__, __CLASS__, (is_object($newNode) ? get_class($newNode) : gettype($newNode))));
        }

        if ($clone) {
            $newNode = $newNode->cloneNode(true);
        }

        if (!$this->getDocument()->is($newNode->ownerDocument)) {
            $newNode = $this->node->ownerDocument->importNode($newNode, true);
        }

        $node = $this->node->parentNode->replaceChild($newNode, $this->node);

        return new Element($node);
    }

    /**
     * Clones a node.
     * 
     * @param bool $deep Indicates whether to copy all descendant nodes
     * 
     * @return \DiDom\Element The cloned node
     */
    public function cloneNode($deep = true)
    {
        return new Element($this->node->cloneNode($deep));
    }

    /**
     * Sets current \DOMElement instance.
     *
     * @param \DOMElement $node
     *
     * @return \DiDom\Element
     */
    protected function setNode(\DOMElement $node)
    {
        $this->node = $node;

        return $this;
    }

    /**
     * Get current \DOMElement instance.
     * 
     * @return \DOMElement
     */
    public function getNode()
    {
        return $this->node;
    }

    /**
     * Returns the document associated with this node.
     * 
     * @return \DiDom\Document|null
     */
    public function getDocument()
    {
        if ($this->node->ownerDocument === null) {
            return null;
        }

        return new Document($this->node->ownerDocument);
    }

    /**
     * Get the DOM document with the current element.
     *
     * @param string $encoding The document encoding
     * 
     * @return \DiDom\Document
     */
    public function toDocument($encoding = 'UTF-8')
    {
        $document = new Document(null, false, $encoding);
        $document->appendChild($this->node);

        return $document;
    }

    /**
     * Dynamically set an attribute on the element.
     *
     * @param string $name The attribute name
     * @param mixed  $value The attribute value
     *
     * @return \DiDom\Element
     */
    public function __set($name, $value)
    {
        return $this->setAttribute($name, $value);
    }

    /**
     * Dynamically access the element's attributes.
     *
     * @param string $name The attribute name
     *
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
     * @param string $name The attribute name
     *
     * @return bool
     */
    public function __isset($name)
    {
        return $this->hasAttribute($name);
    }

    /**
     * Unset an attribute on the model.
     *
     * @param string $name The attribute name
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
     * Searches for an item in the DOM tree for a given XPath expression or a CSS selector.
     * 
     * @param string $expression XPath expression or a CSS selector
     * @param string $type The type of the expression
     * @param bool   $wrapElement Returns array of \DiDom\Element if true, otherwise array of \DOMElement
     *
     * @return \DiDom\Element[]|\DOMElement[]
     */
    public function __invoke($expression, $type = Query::TYPE_CSS, $wrapElement = true)
    {
        return $this->find($expression, $type, $wrapElement);
    }
}
