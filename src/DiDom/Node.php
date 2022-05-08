<?php

declare(strict_types=1);

namespace DiDom;

use DiDom\Exceptions\InvalidSelectorException;
use DOMCdataSection;
use DOMComment;
use DOMDocument;
use DOMDocumentFragment;
use DOMElement;
use DOMNode;
use DOMText;
use InvalidArgumentException;
use LogicException;
use RuntimeException;

/**
 * @property string $tag
 */
abstract class Node
{
    /**
     * The DOM element instance.
     *
     * @var DOMElement|DOMText|DOMComment|DOMCdataSection|DOMDocumentFragment
     */
    protected $node;

    /**
     * Adds a new child at the start of the children.
     *
     * @param Node|DOMNode|array $nodes The prepended child
     *
     * @return Element|Element[]
     *
     * @throws LogicException if the current node has no owner document
     * @throws InvalidArgumentException if one of elements of parameter 1 is not an instance of DOMNode or Element
     */
    public function prependChild($nodes)
    {
        if ($this->node->ownerDocument === null) {
            throw new LogicException('Can not prepend a child to element without the owner document.');
        }

        $returnArray = true;

        if ( ! is_array($nodes)) {
            $nodes = [$nodes];

            $returnArray = false;
        }

        $nodes = array_reverse($nodes);

        $result = [];

        $referenceNode = $this->node->firstChild;

        foreach ($nodes as $node) {
            $result[] = $this->insertBefore($node, $referenceNode);

            $referenceNode = $this->node->firstChild;
        }

        return $returnArray ? $result : $result[0];
    }

    /**
     * Adds a new child at the end of the children.
     *
     * @param Node|DOMNode|array $nodes The appended child
     *
     * @return Element|Element[]
     *
     * @throws LogicException if the current node has no owner document
     * @throws InvalidArgumentException if the provided argument is not an instance of DOMNode or Element
     */
    public function appendChild($nodes)
    {
        if ($this->node->ownerDocument === null) {
            throw new LogicException('Can not append a child to element without the owner document.');
        }

        $returnArray = true;

        if ( ! is_array($nodes)) {
            $nodes = [$nodes];

            $returnArray = false;
        }

        $result = [];

        Errors::disable();

        foreach ($nodes as $node) {
            if ($node instanceof Node) {
                $node = $node->getNode();
            }

            if ( ! $node instanceof DOMNode) {
                throw new InvalidArgumentException(sprintf('Argument 1 passed to %s must be an instance of %s or DOMNode, %s given.', __METHOD__, __CLASS__, (is_object($node) ? get_class($node) : gettype($node))));
            }

            $clonedNode = $node->cloneNode(true);
            $newNode = $this->node->ownerDocument->importNode($clonedNode, true);

            $result[] = $this->node->appendChild($newNode);
        }

        Errors::restore();

        $result = array_map(function (DOMNode $node) {
            return new Element($node);
        }, $result);

        return $returnArray ? $result : $result[0];
    }

    /**
     * Adds a new child before a reference node.
     *
     * @param Node|DOMNode $node The new node
     * @param Element|DOMNode|null $referenceNode The reference node
     *
     * @return Element
     *
     * @throws LogicException if the current node has no owner document
     * @throws InvalidArgumentException if $node is not an instance of DOMNode or Element
     * @throws InvalidArgumentException if $referenceNode is not an instance of DOMNode or Element
     */
    public function insertBefore($node, $referenceNode = null): self
    {
        if ($this->node->ownerDocument === null) {
            throw new LogicException('Can not insert a child to an element without the owner document.');
        }

        if ($node instanceof Node) {
            $node = $node->getNode();
        }

        if ( ! $node instanceof DOMNode) {
            throw new InvalidArgumentException(sprintf('Argument 1 passed to %s must be an instance of %s or DOMNode, %s given.', __METHOD__, __CLASS__, (is_object($node) ? get_class($node) : gettype($node))));
        }

        if ($referenceNode !== null) {
            if ($referenceNode instanceof Element) {
                $referenceNode = $referenceNode->getNode();
            }

            if ( ! $referenceNode instanceof DOMNode) {
                throw new InvalidArgumentException(sprintf('Argument 2 passed to %s must be an instance of %s or DOMNode, %s given.', __METHOD__, __CLASS__, (is_object($referenceNode) ? get_class($referenceNode) : gettype($referenceNode))));
            }
        }

        Errors::disable();

        $clonedNode = $node->cloneNode(true);
        $newNode = $this->node->ownerDocument->importNode($clonedNode, true);

        $insertedNode = $this->node->insertBefore($newNode, $referenceNode);

        Errors::restore();

        return new Element($insertedNode);
    }

    /**
     * Adds a new child after a reference node.
     *
     * @param Node|DOMNode $node The new node
     * @param Element|DOMNode|null $referenceNode The reference node
     *
     * @return Element
     *
     * @throws LogicException if the current node has no owner document
     * @throws InvalidArgumentException if $node is not an instance of DOMNode or Element
     * @throws InvalidArgumentException if $referenceNode is not an instance of DOMNode or Element
     */
    public function insertAfter($node, $referenceNode = null): self
    {
        if ($referenceNode === null) {
            return $this->insertBefore($node);
        }

        if ($referenceNode instanceof Node) {
            $referenceNode = $referenceNode->getNode();
        }

        if ( ! $referenceNode instanceof DOMNode) {
            throw new InvalidArgumentException(sprintf('Argument 2 passed to %s must be an instance of %s or DOMNode, %s given.', __METHOD__, __CLASS__, (is_object($referenceNode) ? get_class($referenceNode) : gettype($referenceNode))));
        }

        return $this->insertBefore($node, $referenceNode->nextSibling);
    }

    /**
     * Adds a new sibling before a reference node.
     *
     * @param Node|DOMNode $node The new node
     *
     * @return Element
     *
     * @throws LogicException if the current node has no owner document
     * @throws InvalidArgumentException if $node is not an instance of DOMNode or Element
     * @throws InvalidArgumentException if $referenceNode is not an instance of DOMNode or Element
     */
    public function insertSiblingBefore($node): self
    {
        if ($this->node->ownerDocument === null) {
            throw new LogicException('Can not insert a child to an element without the owner document.');
        }

        if ($this->parent() === null) {
            throw new LogicException('Can not insert a child to an element without the parent element.');
        }

        if ($node instanceof Node) {
            $node = $node->getNode();
        }

        if ( ! $node instanceof DOMNode) {
            throw new InvalidArgumentException(sprintf('Argument 1 passed to %s must be an instance of %s or DOMNode, %s given.', __METHOD__, __CLASS__, (is_object($node) ? get_class($node) : gettype($node))));
        }

        Errors::disable();

        $clonedNode = $node->cloneNode(true);
        $newNode = $this->node->ownerDocument->importNode($clonedNode, true);

        $insertedNode = $this->parent()->getNode()->insertBefore($newNode, $this->node);

        Errors::restore();

        return new Element($insertedNode);
    }

    /**
     * Adds a new sibling after a reference node.
     *
     * @param Node|DOMNode $node The new node
     *
     * @return Element
     *
     * @throws LogicException if the current node has no owner document
     * @throws InvalidArgumentException if $node is not an instance of DOMNode or Element
     * @throws InvalidArgumentException if $referenceNode is not an instance of DOMNode or Element
     */
    public function insertSiblingAfter($node): self
    {
        if ($this->node->ownerDocument === null) {
            throw new LogicException('Can not insert a child to an element without the owner document.');
        }

        if ($this->parent() === null) {
            throw new LogicException('Can not insert a child to an element without the parent element.');
        }

        $nextSibling = $this->nextSibling();

        // if the current node is the last child
        if ($nextSibling === null) {
            return $this->parent()->appendChild($node);
        }

        return $nextSibling->insertSiblingBefore($node);
    }

    /**
     * Checks the existence of the node.
     *
     * @param string $expression XPath expression or CSS selector
     * @param string $type The type of the expression
     *
     * @return bool
     */
    public function has(string $expression, string $type = Query::TYPE_CSS): bool
    {
        return $this->toDocument()->has($expression, $type);
    }

    /**
     * Searches for a node in the DOM tree for a given XPath expression or CSS selector.
     *
     * @param string $expression XPath expression or CSS selector
     * @param string $type The type of the expression
     * @param bool $wrapElement Returns array of Element if true, otherwise array of DOMElement
     *
     * @return Element[]|DOMElement[]
     *
     * @throws InvalidSelectorException
     */
    public function find(string $expression, string $type = Query::TYPE_CSS, bool $wrapElement = true): array
    {
        return $this->toDocument()->find($expression, $type, $wrapElement);
    }

    /**
     * Searches for a node in the owner document using current node as context.
     *
     * @param string $expression XPath expression or CSS selector
     * @param string $type The type of the expression
     * @param bool $wrapNode Returns array of Element if true, otherwise array of DOMElement
     *
     * @return Element[]|DOMElement[]
     *
     * @throws LogicException if the current node has no owner document
     * @throws InvalidSelectorException
     */
    public function findInDocument(string $expression, string $type = Query::TYPE_CSS, bool $wrapNode = true): array
    {
        $ownerDocument = $this->ownerDocument();

        if ($ownerDocument === null) {
            throw new LogicException('Can not search in context without the owner document.');
        }

        return $ownerDocument->find($expression, $type, $wrapNode, $this->node);
    }

    /**
     * Searches for a node in the DOM tree and returns first element or null.
     *
     * @param string $expression XPath expression or CSS selector
     * @param string $type The type of the expression
     * @param bool $wrapNode Returns Element if true, otherwise DOMElement
     *
     * @return Element|DOMElement|null
     *
     * @throws InvalidSelectorException
     */
    public function first(string $expression, string $type = Query::TYPE_CSS, bool $wrapNode = true)
    {
        return $this->toDocument()->first($expression, $type, $wrapNode);
    }

    /**
     * Searches for a node in the owner document using current node as context and returns first element or null.
     *
     * @param string $expression XPath expression or CSS selector
     * @param string $type The type of the expression
     * @param bool $wrapNode Returns Element if true, otherwise DOMElement
     *
     * @return Element|DOMElement|null
     *
     * @throws InvalidSelectorException
     */
    public function firstInDocument(string $expression, string $type = Query::TYPE_CSS, bool $wrapNode = true)
    {
        $ownerDocument = $this->ownerDocument();

        if ($ownerDocument === null) {
            throw new LogicException('Can not search in context without the owner document.');
        }

        return $ownerDocument->first($expression, $type, $wrapNode, $this->node);
    }

    /**
     * Searches for a node in the DOM tree for a given XPath expression.
     *
     * @param string $expression XPath expression
     * @param bool $wrapNode Returns array of Element if true, otherwise array of DOMElement
     *
     * @return Element[]|DOMElement[]
     *
     * @throws InvalidSelectorException
     */
    public function xpath(string $expression, bool $wrapNode = true): array
    {
        return $this->find($expression, Query::TYPE_XPATH, $wrapNode);
    }

    /**
     * Counts nodes for a given XPath expression or CSS selector.
     *
     * @param string $expression XPath expression or CSS selector
     * @param string $type The type of the expression
     *
     * @return int
     *
     * @throws InvalidSelectorException
     */
    public function count(string $expression, string $type = Query::TYPE_CSS): int
    {
        return $this->toDocument()->count($expression, $type);
    }

    /**
     * Dumps the node into a string using HTML formatting (including child nodes).
     *
     * @return string
     */
    public function html(): string
    {
        return $this->toDocument()->html();
    }

    /**
     * Dumps the node into a string using HTML formatting (without child nodes).
     *
     * @return string
     */
    public function outerHtml(): string
    {
        $document = new DOMDocument();

        $importedNode = $document->importNode($this->node);

        return $document->saveHTML($importedNode);
    }

    /**
     * Dumps the node descendants into a string using HTML formatting.
     *
     * @param string $delimiter
     *
     * @return string
     */
    public function innerHtml(string $delimiter = ''): string
    {
        $innerHtml = [];

        foreach ($this->node->childNodes as $childNode) {
            $innerHtml[] = $childNode->ownerDocument->saveHTML($childNode);
        }

        return implode($delimiter, $innerHtml);
    }

    /**
     * Dumps the node descendants into a string using XML formatting.
     *
     * @param string $delimiter
     *
     * @return string
     */
    public function innerXml(string $delimiter = ''): string
    {
        $innerXml = [];

        foreach ($this->node->childNodes as $childNode) {
            $innerXml[] = $childNode->ownerDocument->saveXML($childNode);
        }

        return implode($delimiter, $innerXml);
    }

    /**
     * Sets inner HTML.
     *
     * @param string $html
     *
     * @return static
     *
     * @throws InvalidArgumentException if passed argument is not a string
     * @throws InvalidSelectorException
     */
    public function setInnerHtml(string $html): self
    {
        return $this->setContent($html, Document::TYPE_HTML);
    }

    /**
     * Sets inner HTML.
     *
     * @param string $xml
     *
     * @return static
     *
     * @throws InvalidArgumentException if passed argument is not a string
     * @throws InvalidSelectorException
     */
    public function setInnerXml(string $xml): self
    {
        return $this->setContent($xml, Document::TYPE_XML);
    }

    protected function setContent(string $content, string $type): self
    {
        $this->removeChildren();

        Errors::disable();

        $encoding = $this->ownerDocument()->getEncoding() ?? 'UTF-8';

        $document = new Document("<didom-fragment>$content</didom-fragment>", false, $encoding, $type);

        $fragment = $document->first('didom-fragment')->getNode();

        foreach ($fragment->childNodes as $node) {
            $newNode = $this->node->ownerDocument->importNode($node, true);

            $this->node->appendChild($newNode);
        }

        Errors::restore();

        return $this;
    }

    /**
     * Dumps the node into a string using XML formatting.
     *
     * @param int $options Additional options
     *
     * @return string The node XML
     */
    public function xml(int $options = 0): string
    {
        return $this->toDocument()->xml($options);
    }

    /**
     * Get the text content of this node and its descendants.
     *
     * @return string The node value
     */
    public function text(): string
    {
        return $this->node->textContent;
    }

    /**
     * Set the value of this node.
     *
     * @param string|integer|float $value The new value of the node
     *
     * @return static
     *
     * @throws InvalidArgumentException if parameter 1 is not a string
     */
    public function setValue($value): self
    {
        if (is_numeric($value)) {
            $value = (string) $value;
        }

        if ( ! is_string($value)) {
            throw new InvalidArgumentException(sprintf('%s expects parameter 1 to be string, integer or float, %s given', __METHOD__, (is_object($value) ? get_class($value) : gettype($value))));
        }

        $this->node->nodeValue = $value;

        return $this;
    }

    /**
     * Returns true if the current node is a DOMElement instance.
     *
     * @return bool
     */
    public function isElementNode(): bool
    {
        return $this->node instanceof DOMElement;
    }

    /**
     * Returns true if the current node is a a DOMText instance.
     *
     * @return bool
     */
    public function isTextNode(): bool
    {
        return $this->node instanceof DOMText;
    }

    /**
     * Returns true if the current node is a DOMComment instance.
     *
     * @return bool
     */
    public function isCommentNode(): bool
    {
        return $this->node instanceof DOMComment;
    }

    /**
     * Returns true if the current node is a DOMCdataSection instance.
     *
     * @return bool
     */
    public function isCdataSectionNode(): bool
    {
        return $this->node instanceof DOMCdataSection;
    }

    /**
     * Indicates if two nodes are the same node.
     *
     * @param Element|DOMNode $node
     *
     * @return bool
     *
     * @throws InvalidArgumentException if parameter 1 is not an instance of DOMNode
     */
    public function is($node): bool
    {
        if ($node instanceof Node) {
            $node = $node->getNode();
        }

        if ( ! $node instanceof DOMNode) {
            throw new InvalidArgumentException(sprintf('Argument 1 passed to %s must be an instance of %s or DOMNode, %s given.', __METHOD__, __CLASS__, (is_object($node) ? get_class($node) : gettype($node))));
        }

        return $this->node->isSameNode($node);
    }

    /**
     * @return Element|Document|null
     */
    public function parent()
    {
        if ($this->node->parentNode === null) {
            return null;
        }

        if ($this->node->parentNode instanceof DOMDocument) {
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
     * @return Element|null
     *
     * @throws InvalidSelectorException if the selector is invalid
     */
    public function closest(string $selector, bool $strict = false): ?Element
    {
        $node = $this;

        while (true) {
            $parent = $node->parent();

            if ($parent === null || $parent instanceof Document) {
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
     * @return Element|null
     *
     * @throws InvalidArgumentException if parameter 2 is not a string
     * @throws RuntimeException if the node type is invalid
     * @throws LogicException if the selector used with non DOMElement node type
     * @throws InvalidSelectorException if the selector is invalid
     */
    public function previousSibling(?string $selector = null, ?string $nodeType = null): ?Element
    {
        if ($this->node->previousSibling === null) {
            return null;
        }

        if ($selector === null && $nodeType === null) {
            return new Element($this->node->previousSibling);
        }

        if ($selector !== null && $nodeType === null) {
            $nodeType = 'DOMElement';
        }

        $allowedTypes = ['DOMElement', 'DOMText', 'DOMComment', 'DOMCdataSection'];

        if ( ! in_array($nodeType, $allowedTypes, true)) {
            throw new RuntimeException(sprintf('Unknown node type "%s". Allowed types: %s', $nodeType, implode(', ', $allowedTypes)));
        }

        if ($selector !== null && $nodeType !== 'DOMElement') {
            throw new LogicException(sprintf('Selector can be used only with DOMElement node type, %s given.', $nodeType));
        }

        $node = $this->node->previousSibling;

        while ($node !== null) {
            if (get_class($node) !== $nodeType) {
                $node = $node->previousSibling;

                continue;
            }

            $element = new Element($node);

            if ($selector === null || $element->matches($selector)) {
                return $element;
            }

            $node = $node->previousSibling;
        }

        return null;
    }

    /**
     * @param string|null $selector
     * @param string|null $nodeType
     *
     * @return Element[]
     *
     * @throws InvalidArgumentException if parameter 2 is not a string
     * @throws RuntimeException if the node type is invalid
     * @throws LogicException if the selector used with non DOMElement node type
     * @throws InvalidSelectorException if the selector is invalid
     */
    public function previousSiblings(?string $selector = null, ?string $nodeType = null): array
    {
        if ($this->node->previousSibling === null) {
            return [];
        }

        if ($selector !== null && $nodeType === null) {
            $nodeType = 'DOMElement';
        }

        if ($nodeType !== null) {
            $allowedTypes = ['DOMElement', 'DOMText', 'DOMComment', 'DOMCdataSection'];

            if ( ! in_array($nodeType, $allowedTypes, true)) {
                throw new RuntimeException(sprintf('Unknown node type "%s". Allowed types: %s', $nodeType, implode(', ', $allowedTypes)));
            }
        }

        if ($selector !== null && $nodeType !== 'DOMElement') {
            throw new LogicException(sprintf('Selector can be used only with DOMElement node type, %s given.', $nodeType));
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
     * @return Element|null
     *
     * @throws InvalidArgumentException if parameter 2 is not a string
     * @throws RuntimeException if the node type is invalid
     * @throws LogicException if the selector used with non DOMElement node type
     * @throws InvalidSelectorException if the selector is invalid
     */
    public function nextSibling(?string $selector = null, ?string $nodeType = null): ?Element
    {
        if ($this->node->nextSibling === null) {
            return null;
        }

        if ($selector === null && $nodeType === null) {
            return new Element($this->node->nextSibling);
        }

        if ($selector !== null && $nodeType === null) {
            $nodeType = 'DOMElement';
        }

        $allowedTypes = ['DOMElement', 'DOMText', 'DOMComment', 'DOMCdataSection'];

        if ( ! in_array($nodeType, $allowedTypes, true)) {
            throw new RuntimeException(sprintf('Unknown node type "%s". Allowed types: %s', $nodeType, implode(', ', $allowedTypes)));
        }

        if ($selector !== null && $nodeType !== 'DOMElement') {
            throw new LogicException(sprintf('Selector can be used only with DOMElement node type, %s given.', $nodeType));
        }

        $node = $this->node->nextSibling;

        while ($node !== null) {
            if (get_class($node) !== $nodeType) {
                $node = $node->nextSibling;

                continue;
            }

            $element = new Element($node);

            if ($selector === null || $element->matches($selector)) {
                return $element;
            }

            $node = $node->nextSibling;
        }

        return null;
    }

    /**
     * @param string|null $selector
     * @param string|null $nodeType
     *
     * @return Element[]
     *
     * @throws InvalidArgumentException if parameter 2 is not a string
     * @throws RuntimeException if the node type is invalid
     * @throws LogicException if the selector used with non DOMElement node type
     * @throws InvalidSelectorException if the selector is invalid
     */
    public function nextSiblings(?string $selector = null, ?string $nodeType = null): array
    {
        if ($this->node->nextSibling === null) {
            return [];
        }

        if ($selector !== null && $nodeType === null) {
            $nodeType = 'DOMElement';
        }

        $allowedTypes = ['DOMElement', 'DOMText', 'DOMComment', 'DOMCdataSection'];

        if ($nodeType !== null && ! in_array($nodeType, $allowedTypes, true)) {
            throw new RuntimeException(sprintf('Unknown node type "%s". Allowed types: %s', $nodeType, implode(', ', $allowedTypes)));
        }

        if ($selector !== null && $nodeType !== 'DOMElement') {
            throw new LogicException(sprintf('Selector can be used only with DOMElement node type, %s given.', $nodeType));
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
     * @param int $index
     *
     * @return Element|null
     */
    public function child(int $index): ?Element
    {
        $child = $this->node->childNodes->item($index);

        return $child === null ? null : new Element($child);
    }

    /**
     * @return Element|null
     */
    public function firstChild(): ?Element
    {
        if ($this->node->firstChild === null) {
            return null;
        }

        return new Element($this->node->firstChild);
    }

    /**
     * @return Element|null
     */
    public function lastChild(): ?Element
    {
        if ($this->node->lastChild === null) {
            return null;
        }

        return new Element($this->node->lastChild);
    }

    /**
     * @return bool
     */
    public function hasChildren(): bool
    {
        return $this->node->hasChildNodes();
    }

    /**
     * @return Element[]
     */
    public function children(): array
    {
        $children = [];

        foreach ($this->node->childNodes as $node) {
            $children[] = new Element($node);
        }

        return $children;
    }

    /**
     * Removes child from list of children.
     *
     * @param Node|DOMNode $childNode
     *
     * @return Element the node that has been removed
     */
    public function removeChild($childNode): Element
    {
        if ($childNode instanceof Node) {
            $childNode = $childNode->getNode();
        }

        if ( ! $childNode instanceof DOMNode) {
            throw new InvalidArgumentException(sprintf('Argument 1 passed to %s must be an instance of %s or DOMNode, %s given.', __METHOD__, __CLASS__, (is_object($childNode) ? get_class($childNode) : gettype($childNode))));
        }

        $removedNode = $this->node->removeChild($childNode);

        return new Element($removedNode);
    }

    /**
     * Removes all child nodes.
     *
     * @return Element[] the nodes that has been removed
     */
    public function removeChildren(): array
    {
        // we need to collect child nodes to array
        // because removing nodes from the DOMNodeList on iterating is not working
        $childNodes = [];

        foreach ($this->node->childNodes as $childNode) {
            $childNodes[] = $childNode;
        }

        $removedNodes = [];

        foreach ($childNodes as $childNode) {
            $removedNode = $this->node->removeChild($childNode);

            $removedNodes[] = new Element($removedNode);
        }

        return $removedNodes;
    }

    /**
     * Removes current node from the parent.
     *
     * @return Element the node that has been removed
     *
     * @throws LogicException if the current node has no parent node
     */
    public function remove(): Element
    {
        if ($this->node->parentNode === null) {
            throw new LogicException('Can not remove an element without the parent node.');
        }

        $removedNode = $this->node->parentNode->removeChild($this->node);

        return new Element($removedNode);
    }

    /**
     * Replaces a child.
     *
     * @param Node|DOMNode $newNode The new node
     * @param bool $clone Clone the node if true, otherwise move it
     *
     * @return Element The node that has been replaced
     *
     * @throws LogicException if the current node has no parent node
     */
    public function replace($newNode, bool $clone = true): Element
    {
        if ($this->node->parentNode === null) {
            throw new LogicException('Can not replace an element without the parent node.');
        }

        if ($newNode instanceof Node) {
            $newNode = $newNode->getNode();
        }

        if ( ! $newNode instanceof DOMNode) {
            throw new InvalidArgumentException(sprintf('Argument 1 passed to %s must be an instance of %s or DOMNode, %s given.', __METHOD__, __CLASS__, (is_object($newNode) ? get_class($newNode) : gettype($newNode))));
        }

        if ($clone) {
            $newNode = $newNode->cloneNode(true);
        }

        if ($newNode->ownerDocument === null || ! $this->ownerDocument()->is($newNode->ownerDocument)) {
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
    public function getLineNo(): int
    {
        return $this->node->getLineNo();
    }

    /**
     * Clones a node.
     *
     * @param bool $deep Indicates whether to copy all descendant nodes
     *
     * @return Element The cloned node
     */
    public function cloneNode(bool $deep = true): Element
    {
        return new Element($this->node->cloneNode($deep));
    }

    /**
     * Sets current node instance.
     *
     * @param DOMElement|DOMText|DOMComment|DOMCdataSection|DOMDocumentFragment $node
     *
     * @return static
     */
    protected function setNode($node): self
    {
        $allowedClasses = ['DOMElement', 'DOMText', 'DOMComment', 'DOMCdataSection', 'DOMDocumentFragment'];

        if ( ! is_object($node) || ! in_array(get_class($node), $allowedClasses, true)) {
            throw new InvalidArgumentException(sprintf('Argument 1 passed to %s must be an instance of DOMElement, DOMText, DOMComment, DOMCdataSection or DOMDocumentFragment, %s given.', __METHOD__, (is_object($node) ? get_class($node) : gettype($node))));
        }

        $this->node = $node;

        return $this;
    }

    /**
     * Returns current node instance.
     *
     * @return DOMElement|DOMText|DOMComment|DOMCdataSection|DOMDocumentFragment
     */
    public function getNode()
    {
        return $this->node;
    }

    /**
     * Returns the document associated with this node.
     *
     * @return Document|null
     */
    public function ownerDocument(): ?Document
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
     * @return Document
     */
    public function toDocument(string $encoding = 'UTF-8'): Document
    {
        $document = new Document(null, false, $encoding);

        $document->appendChild($this->node);

        return $document;
    }

    /**
     * Convert the element to its string representation.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->html();
    }
}
