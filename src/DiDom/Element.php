<?php

namespace DiDom;

use DOMDocument;
use DOMNode;
use DOMElement;
use InvalidArgumentException;
use phpDocumentor\Reflection\DocBlock\Tags\Param;
use RuntimeException;
use LogicException;

class Element
{
    /**
     * The DOM element instance.
     *
     * @var \DOMElement|\DOMText|\DOMComment
     */
    protected $node;

    /**
     * Constructor.
     *
     * @param \DOMElement|\DOMText|\DOMComment|string $name The tag name of the element
     * @param string|null $value The value of the element
     * @param array $attributes The attributes of the element
     *
     * @throws \InvalidArgumentException if the attributes is not an array
     */
    public function __construct($name, $value = null, array $attributes = [])
    {
        if (is_string($name)) {
            $document = new DOMDocument('1.0', 'UTF-8');

            $node = $document->createElement($name);

            $this->setNode($node);
        } else {
            $this->setNode($name);
        }

        if ($value !== null) {
            $this->setValue($value);
        }

        foreach ($attributes as $name => $value) {
            $this->setAttribute($name, $value);
        }
    }

    /**
     * Create new element.
     *
     * @param \DOMNode|string $name The tag name of the element
     * @param string|null $value The value of the element
     * @param array  $attributes The attributes of the element
     *
     * @return \DiDom\Element
     *
     * @throws \InvalidArgumentException if the attributes is not an array
     */
    public static function create($name, $value = null, array $attributes = [])
    {
        return new Element($name, $value, $attributes);
    }

    /**
     * Create new element node by CSS selector.
     *
     * @param string $selector
     * @param string|null $value
     * @param array $attributes
     *
     * @return \DiDom\Element
     */
    public static function createBySelector($selector, $value = null, array $attributes = [])
    {
        return Document::create()->createElementBySelector($selector, $value, $attributes);
    }

    /**
     * Adds new child at the end of the children.
     *
     * @param \DiDom\Element|\DOMNode|array $nodes The appended child
     *
     * @return \DiDom\Element|\DiDom\Element[]
     *
     * @throws \LogicException if current node has no owner document
     * @throws \InvalidArgumentException if the provided argument is not an instance of \DOMNode or \DiDom\Element
     */
    public function appendChild($nodes)
    {
        if ($this->node->ownerDocument === null) {
            throw new LogicException('Can not append child to element without owner document');
        }

        $returnArray = true;

        if (!is_array($nodes)) {
            $nodes = [$nodes];

            $returnArray = false;
        }

        $result = [];

        foreach ($nodes as $node) {
            if ($node instanceof Element) {
                $node = $node->getNode();
            }

            if (!$node instanceof \DOMNode) {
                throw new InvalidArgumentException(sprintf('Argument 1 passed to %s must be an instance of %s\Element or DOMNode, %s given', __METHOD__, __NAMESPACE__, (is_object($node) ? get_class($node) : gettype($node))));
            }

            Errors::disable();

            $cloned = $node->cloneNode(true);
            $newNode = $this->node->ownerDocument->importNode($cloned, true);

            $result[] = $this->node->appendChild($newNode);

            Errors::restore();
        }

        $result = array_map(function (\DOMNode $node) {
            return new Element($node);
        }, $result);

        return $returnArray ? $result : $result[0];
    }

    /**
     * Checks the existence of the node.
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
     * Searches for an node in the DOM tree for a given XPath expression or a CSS selector.
     *
     * @param string $expression  XPath expression or a CSS selector
     * @param string $type        The type of the expression
     * @param bool   $wrapElement Returns array of \DiDom\Element if true, otherwise array of \DOMElement
     *
     * @return \DiDom\Element[]|\DOMElement[]
     */
    public function find($expression, $type = Query::TYPE_CSS, $wrapElement = true)
    {
        return $this->toDocument()->find($expression, $type, $wrapElement);
    }

    /**
     * Searches for an node in the owner document using current node as context.
     *
     * @param string $expression XPath expression or a CSS selector
     * @param string $type       The type of the expression
     * @param bool   $wrapNode   Returns array of \DiDom\Element if true, otherwise array of \DOMElement
     *
     * @return \DiDom\Element[]|\DOMElement[]
     *
     * @throws \LogicException if current node has no owner document
     */
    public function findInDocument($expression, $type = Query::TYPE_CSS, $wrapNode = true)
    {
        $ownerDocument = $this->getDocument();

        if ($ownerDocument === null) {
            throw new LogicException('Can not search in context without owner document');
        }

        return $ownerDocument->find($expression, $type, $wrapNode, $this->node);
    }

    /**
     * Searches for an node in the DOM tree and returns first element or null.
     *
     * @param string $expression XPath expression or a CSS selector
     * @param string $type       The type of the expression
     * @param bool   $wrapNode   Returns \DiDom\Element if true, otherwise \DOMElement
     *
     * @return \DiDom\Element|\DOMElement|null
     */
    public function first($expression, $type = Query::TYPE_CSS, $wrapNode = true)
    {
        return $this->toDocument()->first($expression, $type, $wrapNode);
    }

    /**
     * Searches for an node in the owner document using current node as context and returns first element or null.
     *
     * @param string $expression XPath expression or a CSS selector
     * @param string $type       The type of the expression
     * @param bool   $wrapNode   Returns \DiDom\Element if true, otherwise \DOMElement
     *
     * @return \DiDom\Element|\DOMElement|null
     */
    public function firstInDocument($expression, $type = Query::TYPE_CSS, $wrapNode = true)
    {
        $ownerDocument = $this->getDocument();

        if ($ownerDocument === null) {
            throw new LogicException('Can not search in context without owner document');
        }

        return $ownerDocument->first($expression, $type, $wrapNode, $this->node);
    }

    /**
     * Searches for an node in the DOM tree for a given XPath expression.
     *
     * @param string $expression XPath expression
     * @param bool   $wrapNode Returns array of \DiDom\Element if true, otherwise array of \DOMElement
     *
     * @return \DiDom\Element[]|\DOMElement[]
     */
    public function xpath($expression, $wrapNode = true)
    {
        return $this->find($expression, Query::TYPE_XPATH, $wrapNode);
    }

    /**
     * Counts nodes for a given XPath expression or a CSS selector.
     *
     * @param string $expression XPath expression or CSS selector
     * @param string $type The type of the expression
     *
     * @return int
     */
    public function count($expression, $type = Query::TYPE_CSS)
    {
        return $this->toDocument()->count($expression, $type);
    }

    /**
     * Checks that the node matches selector.
     *
     * @param string $selector CSS selector
     * @param bool $strict
     *
     * @return bool
     *
     * @throws \RuntimeException if tag name is not specified in strict mode
     */
    public function matches($selector, $strict = false)
    {
        if (!is_string($selector)) {
            throw new InvalidArgumentException(sprintf('%s expects parameter 1 to be string, %s given', __METHOD__, gettype($selector)));
        }

        if (!$this->node instanceof \DOMElement) {
            return false;
        }

        if ($selector === '*') {
            return true;
        }

        if (!$strict) {
            $innerHtml = $this->html();
            $html = "<root>$innerHtml</root>";

            $selector = 'root > '.trim($selector);

            $document = new Document();

            $document->loadHtml($html, LIBXML_HTML_NODEFDTD | LIBXML_HTML_NOIMPLIED);

            return $document->has($selector);
        }

        $segments = Query::getSegments($selector);

        if (!array_key_exists('tag', $segments)) {
            throw new RuntimeException(sprintf('Tag name must be specified in %s', $selector));
        }

        if ($segments['tag'] !== $this->tag and $segments['tag'] !== '*') {
            return false;
        }

        $segments['id'] = array_key_exists('id', $segments) ? $segments['id'] : null;

        if ($segments['id'] !== $this->getAttribute('id')) {
            return false;
        }

        $classes = $this->hasAttribute('class') ? explode(' ', trim($this->getAttribute('class'))) : [];

        $segments['classes'] = array_key_exists('classes', $segments) ? $segments['classes'] : [];

        $diff1 = array_diff($segments['classes'], $classes);
        $diff2 = array_diff($classes, $segments['classes']);

        if (count($diff1) > 0 or count($diff2) > 0) {
            return false;
        }

        $attributes = $this->attributes();

        unset($attributes['id']);
        unset($attributes['class']);

        $segments['attributes'] = array_key_exists('attributes', $segments) ? $segments['attributes'] : [];

        $diff1 = array_diff_assoc($segments['attributes'], $attributes);
        $diff2 = array_diff_assoc($attributes, $segments['attributes']);

        if (count($diff1) > 0 or count($diff2) > 0) {
            return false;
        }

        return true;
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
        if (is_numeric($value)) {
            $value = (string) $value;
        }

        if (!is_string($value) and $value !== null) {
            throw new InvalidArgumentException(sprintf('%s expects parameter 2 to be string or null, %s given', __METHOD__, (is_object($value) ? get_class($value) : gettype($value))));
        }

        $this->node->setAttribute($name, $value);

        return $this;
    }

    /**
     * Access to the element's attributes.
     *
     * @param string $name The attribute name
     * @param string|null $default The value returned if the attribute does not exist
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
     * Unset all attributes of the element.
     *
     * @param string[] $exclusions
     *
     * @return \DiDom\Element
     */
    public function removeAllAttributes(array $exclusions = [])
    {
        if (!$this->node instanceof DOMElement) {
            return $this;
        }

        foreach ($this->attributes() as $name => $value) {
            if (in_array($name, $exclusions)) {
                continue;
            }

            $this->node->removeAttribute($name);
        }

        return $this;
    }

    /**
     * Alias for getAttribute and setAttribute methods.
     *
     * @param string $name The attribute name
     * @param string|null $value The attribute value or null if the attribute does not exist
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
     * Returns the node attributes or null, if it is not DOMElement.
     *
     * @param string[] $names
     *
     * @return array|null
     */
    public function attributes(array $names = null)
    {
        if (!$this->node instanceof DOMElement) {
            return null;
        }

        if ($names === null) {
            $result = [];

            foreach ($this->node->attributes as $name => $attribute) {
                $result[$name] = $attribute->value;
            }

            return $result;
        }

        $result = [];

        foreach ($this->node->attributes as $name => $attribute) {
            if (in_array($name, $names)) {
                $result[$name] = $attribute->value;
            }
        }

        return $result;
    }

    /**
     * Dumps the node into a string using HTML formatting.
     *
     * @return string The node HTML
     */
    public function html()
    {
        return $this->toDocument()->html();
    }

    /**
     * Dumps the node descendants into a string using HTML formatting.
     *
     * @param string $delimiter
     *
     * @return string
     */
    public function innerHtml($delimiter = '')
    {
        $innerHtml = [];

        foreach ($this->node->childNodes as $childNode)
        {
            $innerHtml[] = $childNode->ownerDocument->saveHTML($childNode);
        }

        return implode($delimiter, $innerHtml);
    }

    /**
     * Sets inner HTML.
     *
     * @param string $html
     *
     * @return Element
     */
    public function setInnerHtml($html)
    {
        if (!is_string($html)) {
            throw new InvalidArgumentException(sprintf('%s expects parameter 1 to be string, %s given', __METHOD__, (is_object($html) ? get_class($html) : gettype($html))));
        }

        $this->removeChildren();

        if ($html !== '') {
            Errors::disable();

            $html = "<htmlfragment>$html</htmlfragment>";

            $document = new Document($html);

            $fragment = $document->first('htmlfragment')->getNode();

            foreach ($fragment->childNodes as $node) {
                $newNode = $this->node->ownerDocument->importNode($node, true);

                $this->node->appendChild($newNode);
            }

            Errors::restore();
        }

        return $this;
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
     *
     * @throws \InvalidArgumentException if value is not string
     */
    public function setValue($value)
    {
        if (is_numeric($value)) {
            $value = (string) $value;
        }

        if (!is_string($value) and $value !== null) {
            throw new InvalidArgumentException(sprintf('%s expects parameter 1 to be string, %s given', __METHOD__, (is_object($value) ? get_class($value) : gettype($value))));
        }

        $this->node->nodeValue = $value;

        return $this;
    }

    /**
     * Returns true if current node is DOMElement.
     *
     * @return bool
     */
    public function isElementNode()
    {
        return $this->node instanceof \DOMElement;
    }

    /**
     * Returns true if current node is DOMText.
     *
     * @return bool
     */
    public function isTextNode()
    {
        return $this->node instanceof \DOMText;
    }

    /**
     * Returns true if current node is DOMComment.
     *
     * @return bool
     */
    public function isCommentNode()
    {
        return $this->node instanceof \DOMComment;
    }

    /**
     * Indicates if two nodes are the same node.
     *
     * @param \DiDom\Element|\DOMNode $node
     *
     * @return bool
     *
     * @throws \InvalidArgumentException if the provided argument is not an instance of \DOMNode
     */
    public function is($node)
    {
        if ($node instanceof self) {
            $node = $node->getNode();
        }

        if (!$node instanceof DOMNode) {
            throw new InvalidArgumentException(sprintf('Argument 1 passed to %s must be an instance of %s or DOMNode, %s given', __METHOD__, __CLASS__, (is_object($node) ? get_class($node) : gettype($node))));
        }

        return $this->node->isSameNode($node);
    }

    /**
     * @return \DiDom\Element|\DiDom\Document|null
     */
    public function parent()
    {
        if ($this->node->parentNode === null) {
            return null;
        }

        if ($this->node->parentNode instanceof \DOMDocument) {
            return new Document($this->node->parentNode);
        }

        return new Element($this->node->parentNode);
    }

    /**
     * Returns first parent node matches passed selector.
     *
     * @param string $selector
     * @param bool $strict
     *
     * @return \DiDom\Element|null
     */
    public function closest($selector, $strict = false)
    {
        $node = $this;

        while (true) {
            $parent = $node->parent();

            if ($parent === null or $parent instanceof Document) {
                return null;
            }

            if ($parent->matches($selector, $strict)) {
                return $parent;
            }

            $node = $parent;
        }
    }

    /**
     * @param string|null $selector
     * @param string|null $nodeType
     *
     * @return \DiDom\Element|null
     *
     * @throws \InvalidArgumentException if node type is not string
     * @throws \RuntimeException if node type is invalid
     * @throws \LogicException if selector used with non DOMElement node type
     */
    public function previousSibling($selector = null, $nodeType = null)
    {
        if ($this->node->previousSibling === null) {
            return null;
        }

        if ($selector === null and $nodeType === null) {
            return new Element($this->node->previousSibling);
        }

        if ($selector !== null and $nodeType === null) {
            $nodeType = 'DOMElement';
        }

        if (!is_string($nodeType)) {
            throw new InvalidArgumentException(sprintf('%s expects parameter 2 to be string, %s given', __METHOD__, gettype($nodeType)));
        }

        $allowedTypes = ['DOMElement', 'DOMText', 'DOMComment'];

        if (!in_array($nodeType, $allowedTypes)) {
            throw new RuntimeException(sprintf('Unknown node type "%s". Allowed types: %s', $nodeType, implode(', ', $allowedTypes)));
        }

        if ($selector !== null and $nodeType !== 'DOMElement') {
            throw new LogicException(sprintf('Selector can be used only with DOMElement node type, %s given', $nodeType));
        }

        $node = $this->node->previousSibling;

        while ($node !== null) {
            if (get_class($node) !== $nodeType) {
                $node = $node->previousSibling;

                continue;
            }

            $element = new Element($node);

            if ($selector === null) {
                return $element;
            }

            if ($element->matches($selector)) {
                return $element;
            }

            $node = $node->previousSibling;
        }

        return new Element($this->node->previousSibling);
    }

    /**
     * @param string|null $selector
     * @param string|null $nodeType
     *
     * @return \DiDom\Element[]
     *
     * @throws \InvalidArgumentException if node type is not string
     * @throws \RuntimeException if node type is invalid
     * @throws \LogicException if selector used with non DOMElement node type
     */
    public function previousSiblings($selector = null, $nodeType = null)
    {
        if ($this->node->previousSibling === null) {
            return [];
        }

        if ($selector !== null and $nodeType === null) {
            $nodeType = 'DOMElement';
        }

        if ($nodeType !== null) {
            if (!is_string($nodeType)) {
                throw new InvalidArgumentException(sprintf('%s expects parameter 2 to be string, %s given', __METHOD__, gettype($nodeType)));
            }

            $allowedTypes = ['DOMElement', 'DOMText', 'DOMComment'];

            if (!in_array($nodeType, $allowedTypes)) {
                throw new RuntimeException(sprintf('Unknown node type "%s". Allowed types: %s', $nodeType, implode(', ', $allowedTypes)));
            }
        }

        if ($selector !== null and $nodeType !== 'DOMElement') {
            throw new LogicException(sprintf('Selector can be used only with DOMElement node type, %s given', $nodeType));
        }

        $result = [];

        $node = $this->node->previousSibling;

        while ($node !== null) {
            $element = new Element($node);

            if ($nodeType === null) {
                $result[] = $element;

                $node = $node->previousSibling;

                continue;
            }

            if (get_class($node) !== $nodeType) {
                $node = $node->previousSibling;

                continue;
            }

            if ($selector === null) {
                $result[] = $element;

                $node = $node->previousSibling;

                continue;
            }

            if ($element->matches($selector)) {
                $result[] = $element;
            }

            $node = $node->previousSibling;
        }

        return array_reverse($result);
    }

    /**
     * @param string|null $selector
     * @param string|null $nodeType
     *
     * @return \DiDom\Element|null
     *
     * @throws \InvalidArgumentException if node type is not string
     * @throws \RuntimeException if node type is invalid
     * @throws \LogicException if selector used with non DOMElement node type
     */
    public function nextSibling($selector = null, $nodeType = null)
    {
        if ($this->node->nextSibling === null) {
            return null;
        }

        if ($selector === null and $nodeType === null) {
            return new Element($this->node->nextSibling);
        }

        if ($selector !== null and $nodeType === null) {
            $nodeType = 'DOMElement';
        }

        if (!is_string($nodeType)) {
            throw new InvalidArgumentException(sprintf('%s expects parameter 2 to be string, %s given', __METHOD__, gettype($nodeType)));
        }

        $allowedTypes = ['DOMElement', 'DOMText', 'DOMComment'];

        if (!in_array($nodeType, $allowedTypes)) {
            throw new RuntimeException(sprintf('Unknown node type "%s". Allowed types: %s', $nodeType, implode(', ', $allowedTypes)));
        }

        if ($selector !== null and $nodeType !== 'DOMElement') {
            throw new LogicException(sprintf('Selector can be used only with DOMElement node type, %s given', $nodeType));
        }

        $node = $this->node->nextSibling;

        while ($node !== null) {
            if (get_class($node) !== $nodeType) {
                $node = $node->nextSibling;

                continue;
            }

            $element = new Element($node);

            if ($selector === null) {
                return $element;
            }

            if ($element->matches($selector)) {
                return $element;
            }

            $node = $node->nextSibling;
        }

        return null;
    }

    /**
     * @param string|null $selector
     * @param string $nodeType
     *
     * @return \DiDom\Element[]
     *
     * @throws \InvalidArgumentException if node type is not string
     * @throws \RuntimeException if node type is invalid
     * @throws \LogicException if selector used with non DOMElement node type
     */
    public function nextSiblings($selector = null, $nodeType = null)
    {
        if ($this->node->nextSibling === null) {
            return [];
        }

        if ($selector !== null and $nodeType === null) {
            $nodeType = 'DOMElement';
        }

        if ($nodeType !== null) {
            if (!is_string($nodeType)) {
                throw new InvalidArgumentException(sprintf('%s expects parameter 2 to be string, %s given', __METHOD__, gettype($nodeType)));
            }

            $allowedTypes = ['DOMElement', 'DOMText', 'DOMComment'];

            if (!in_array($nodeType, $allowedTypes)) {
                throw new RuntimeException(sprintf('Unknown node type "%s". Allowed types: %s', $nodeType, implode(', ', $allowedTypes)));
            }
        }

        if ($selector !== null and $nodeType !== 'DOMElement') {
            throw new LogicException(sprintf('Selector can be used only with DOMElement node type, %s given', $nodeType));
        }

        $result = [];

        $node = $this->node->nextSibling;

        while ($node !== null) {
            $element = new Element($node);

            if ($nodeType === null) {
                $result[] = $element;

                $node = $node->nextSibling;

                continue;
            }

            if (get_class($node) !== $nodeType) {
                $node = $node->nextSibling;

                continue;
            }

            if ($selector === null) {
                $result[] = $element;

                $node = $node->nextSibling;

                continue;
            }

            if ($element->matches($selector)) {
                $result[] = $element;
            }

            $node = $node->nextSibling;
        }

        return $result;
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
    public function hasChildren()
    {
        return $this->node->hasChildNodes();
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
     * @param \DOMNode|\DiDom\Element $childNode
     *
     * @return \DiDom\Element the node that has been removed
     */
    public function removeChild($childNode)
    {
        if ($childNode instanceof self) {
            $childNode = $childNode->getNode();
        }

        if (!$childNode instanceof DOMNode) {
            throw new InvalidArgumentException(sprintf('Argument 1 passed to %s must be an instance of %s or DOMNode, %s given', __METHOD__, __CLASS__, (is_object($childNode) ? get_class($childNode) : gettype($childNode))));
        }

        $removedNode = $this->node->removeChild($childNode);

        return new Element($removedNode);
    }

    /**
     * Removes all child nodes.
     *
     * @return \DiDom\Element the nodes that has been removed
     */
    public function removeChildren()
    {
        // we need to collect child nodes to array
        // because removing nodes from the DOMNodeList on iterating is not working
        $childNodes = [];

        foreach ($this->node->childNodes as $childNode)
        {
            $childNodes[] = $childNode;
        }

        $removedNodes = [];

        foreach ($childNodes as $childNode)
        {
            $removedNode = $this->node->removeChild($childNode);

            $removedNodes[] = new Element($removedNode);
        }

        return $removedNodes;
    }

    /**
     * Removes current node from the parent.
     *
     * @return \DiDom\Element the node that has been removed
     *
     * @throws \LogicException if current node has no parent node
     */
    public function remove()
    {
        if ($this->node->parentNode === null) {
            throw new LogicException('Can not remove element without parent node');
        }

        $removedNode = $this->node->parentNode->removeChild($this->node);

        return new Element($removedNode);
    }

    /**
     * Replaces a child.
     *
     * @param \DOMNode|\DiDom\Element $newNode The new node
     * @param bool $clone Clone the node if true, otherwise move it
     *
     * @return \DiDom\Element The node that has been replaced
     *
     * @throws \LogicException if current node has no parent node
     */
    public function replace($newNode, $clone = true)
    {
        if ($this->node->parentNode === null) {
            throw new LogicException('Can not replace element without parent node');
        }

        if ($newNode instanceof self) {
            $newNode = $newNode->getNode();
        }

        if (!$newNode instanceof DOMNode) {
            throw new InvalidArgumentException(sprintf('Argument 1 passed to %s must be an instance of %s or DOMNode, %s given', __METHOD__, __CLASS__, (is_object($newNode) ? get_class($newNode) : gettype($newNode))));
        }

        if ($clone) {
            $newNode = $newNode->cloneNode(true);
        }

        if ($newNode->ownerDocument === null or !$this->getDocument()->is($newNode->ownerDocument)) {
            $newNode = $this->node->ownerDocument->importNode($newNode, true);
        }

        $node = $this->node->parentNode->replaceChild($newNode, $this->node);

        return new Element($node);
    }

    /**
     * Get line number for a node.
     *
     * @return int
     */
    public function getLineNo()
    {
        return $this->node->getLineNo();
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
     * Sets current node instance.
     *
     * @param \DOMElement|\DOMText|\DOMComment $node
     *
     * @return \DiDom\Element
     */
    protected function setNode($node)
    {
        $allowedClasses = ['DOMElement', 'DOMText', 'DOMComment'];

        if (!in_array(get_class($node), $allowedClasses)) {
            throw new InvalidArgumentException(sprintf('Argument 1 passed to %s must be an instance of DOMElement, DOMText or DOMComment, %s given', __METHOD__, (is_object($node) ? get_class($node) : gettype($node))));
        }

        $this->node = $node;

        return $this;
    }

    /**
     * Returns current node instance.
     *
     * @return \DOMElement|\DOMText|\DOMComment
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
     * Searches for an node in the DOM tree for a given XPath expression or a CSS selector.
     *
     * @param string $expression XPath expression or a CSS selector
     * @param string $type       The type of the expression
     * @param bool   $wrapNode   Returns array of \DiDom\Element if true, otherwise array of \DOMElement
     *
     * @return \DiDom\Element[]|\DOMElement[]
     */
    public function __invoke($expression, $type = Query::TYPE_CSS, $wrapNode = true)
    {
        return $this->find($expression, $type, $wrapNode);
    }
}
