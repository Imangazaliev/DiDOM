<?php

namespace DiDom;

use DOMDocument;
use DOMNode;
use DOMXPath;
use InvalidArgumentException;
use RuntimeException;

class Document
{
    /**
     * @var \DOMDocument
     */
    protected $document;

    /**
     * Constructor.
     * 
     * @param  string $html HTML code or file path
     * @param  bool   $isFile indicates that in first parameter was passed to the file path
     * @param  string $encoding The document encoding
     */
    public function __construct($html = null, $isFile = false, $encoding = 'UTF-8')
    {
        if ($html instanceof DOMDocument) {
            $this->document = $html;

            return;
        }

        $this->document = new DOMDocument('1.0', $encoding);

        if ($html !== null) {
            if ($isFile) {
                $this->loadHtmlFile($html);
            } else {
                $this->loadHtml($html);
            }
        }
    }

    /**
     * Create new element node.
     * 
     * @param  string $name The tag name of the element
     * @param  string $value The value of the element
     * @param  array  $attributes The attributes of the element
     *
     * @return \DiDom\Element created element
     */
    public function createElement($name, $value = '', $attributes = [])
    {
        $node = $this->document->createElement($name, $value);

        return new Element($node, null, $attributes);
    }

    /**
     * Adds new child at the end of the children.
     * 
     * @param  \DiDom\Element|\DOMNode $element The appended child.
     *
     * @return \DiDom\Document
     *
     * @throws \InvalidArgumentException if the provided argument is not an instance of \DOMNode or \DiDom\Element
     */
    public function appendChild($node)
    {
        if ($node instanceof Element) {
            $node = $node->getNode();
        }

        if (!$node instanceof DOMNode) {
            throw new InvalidArgumentException(sprintf('Argument 1 passed to %s must be an instance of %s\Element or %s, %s given', __METHOD__, __NAMESPACE__, 'DOMNode', (is_object($node) ? get_class($node) : gettype($node))));
        }

        $cloned = $node->cloneNode(true);
        $newNode = $this->document->importNode($cloned, true);

        $this->document->appendChild($newNode);

        return $this;
    }

    /**
     * Load HTML from a string.
     * 
     * @param  string $html The HTML string
     *
     * @return \DiDom\Document
     *
     * @throws \InvalidArgumentException if the provided argument is not a string
     */
    public function loadHtml($html)
    {
        if (!is_string($html)) {
            throw new InvalidArgumentException(sprintf('%s expects parameter 1 to be string, %s given', __METHOD__, (is_object($html) ? get_class($html) : gettype($html))));
        }

        $prolog = sprintf('<?xml encoding="%s">', $this->document->encoding);
        $html = $prolog.$html;

        libxml_use_internal_errors(true);
        libxml_disable_entity_loader(true);

        $this->document->loadHtml($html);

        libxml_clear_errors();

        libxml_disable_entity_loader(false);
        libxml_use_internal_errors(false);

        return $this;
    }

    /**
     * Load HTML from a file.
     * 
     * @param  string $filepath The path to the HTML file
     *
     * @return \DiDom\Document
     *
     * @throws \InvalidArgumentException if the file path is not a string
     * @throws \RuntimeException if the file does not exist
     * @throws \RuntimeException if you are unable to load the file
     */
    public function loadHtmlFile($filepath)
    {
        if (!is_string($filepath)) {
            throw new InvalidArgumentException(sprintf('%s expects parameter 1 to be string, %s given', __METHOD__, gettype($filepath)));
        }

        if (filter_var($filepath, FILTER_VALIDATE_URL) === false) {
            if (!file_exists($filepath)) {
                throw new RuntimeException(sprintf('File %s not found', $filepath));
            }
        }

        $html = file_get_contents($filepath);

        if ($html === false) {
            throw new RuntimeException(sprintf('Could not load file %s', $filepath));
        }

        $this->loadHtml($html);

        return $this;
    }

    /**
     * Checks the existence of the item.
     * 
     * @param  string $expression XPath expression or CSS selector
     * @param  string $type the type of the expression
     *
     * @return bool
     */
    public function has($expression, $type = Query::TYPE_CSS)
    {
        return count($this->find($expression, $type)) > 0;
    }

    /**
     * Searches for an item in the DOM tree for a given XPath expression or a CSS selector.
     * 
     * @param  string $expression XPath expression or a CSS selector
     * @param  string $type the type of the expression
     * @param  bool   $wrapElement returns array of \DiDom\Element if true, otherwise array of \DOMElement
     *
     * @return \DiDom\Element[]|\DOMElement[]
     */
    public function find($expression, $type = Query::TYPE_CSS, $wrapElement = true)
    {
        $expression = Query::compile($expression, $type);

        $xpath    = new DOMXPath($this->document);
        $nodeList = $xpath->query($expression);
        $elements = array();

        if ($wrapElement) {
            foreach ($nodeList as $node) {
                $elements[] = new Element($node);
            }
        } else {
            foreach ($nodeList as $node) {
                $elements[] = $node;
            }
        }

        return $elements;
    }

    /**
     * Searches for an item in the DOM tree for a given XPath expression.
     * 
     * @param  string $expression XPath expression
     * @param  bool   $wrapElement returns array of \DiDom\Element if true, otherwise array of \DOMElement
     *
     * @return \DiDom\Element[]|\DOMElement[]
     */
    public function xpath($expression, $wrapElement = true)
    {
        return $this->find($expression, Query::TYPE_XPATH, $wrapElement);
    }

    /**
     * Dumps the internal document into a string using HTML formatting.
     * 
     * @return string The document html
     */
    public function html()
    {
        return trim($this->document->saveXML($this->getElement()));
    }

    /**
     * Nicely formats output with indentation and extra space.
     * 
     * @param  bool $format formats output if true
     *
     * @return \DiDom\Document
     */
    public function format($format = true)
    {
        $this->document->formatOutput = $format;

        return $this;
    }

    /**
     * Get the text content of this node and its descendants.
     * 
     * @return string
     */
    public function text()
    {
        return $this->getElement()->textContent;
    }

    /**
     * Indicates if two documents are the same document.
     * 
     * @param  Document|\DOMDocument $document The compared document.
     *
     * @return bool
     *
     * @throws \InvalidArgumentException if the provided argument is not an instance of \DOMDocument or \DiDom\Document
     */
    public function is($document)
    {
        if ($document instanceof self) {
            $element = $document->getElement();
        } else {
            if (!$document instanceof DOMDocument) {
                throw new InvalidArgumentException(sprintf('Argument 1 passed to %s must be an instance of %s or %s, %s given', __METHOD__, __CLASS__, 'DOMDocument', (is_object($document) ? get_class($document) : gettype($document))));
            }

            $element = $document->documentElement;
        }

        return $this->getElement()->isSameNode($element);
    }

    /**
     * @return \DOMDocument
     */
    public function getDocument()
    {
        return $this->document;
    }

    /**
     * @return \DOMElement
     */
    public function getElement()
    {
        return $this->document->documentElement;
    }

    /**
     * @return \DiDom\Element
     */
    public function toElement()
    {
        return new Element($this->getElement());
    }

    /**
     * Convert the document to its string representation.
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
     * @param  string $expression XPath expression or a CSS selector
     * @param  string $type the type of the expression
     * @param  bool   $wrapElement returns array of \DiDom\Element if true, otherwise array of \DOMElement
     *
     * @return \DiDom\Element[]|\DOMElement[]
     */
    public function __invoke($expression, $type = Query::TYPE_CSS, $wrapElement = true)
    {
        return $this->find($expression, $type, $wrapElement);
    }
}
