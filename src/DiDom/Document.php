<?php

namespace DiDom;

use DOMDocument;
use DOMXPath;
use InvalidArgumentException;
use RuntimeException;

class Document
{
    use common;
    
    /**
     * @var \DOMDocument
     */
    protected $document;

    /**
     * @var string
     */
    protected $type;

    /**
     * Constructor.
     * 
     * @param string $string HTML or XML string or file path
     * @param bool   $isFile Indicates that in first parameter was passed to the file path
     * @param string $encoding The document encoding
     * @param string $type The document type
     */
    public function __construct($string = null, $isFile = false, $encoding = 'UTF-8', $type = 'html')
    {
        if ($string instanceof DOMDocument) {
            $this->document = $string;

            return;
        }

        if (!is_string($encoding)) {
            throw new InvalidArgumentException(sprintf('%s expects parameter 3 to be string, %s given', __METHOD__, gettype($encoding)));
        }

        $this->document = new DOMDocument('1.0', $encoding);

        $this->preserveWhiteSpace(false);

        if ($string !== null) {
            $this->load($string, $isFile, $type);
        }
    }

    /**
     * Create new document.
     *
     * @param string $string HTML or XML string or file path
     * @param bool   $isFile Indicates that in first parameter was passed to the file path
     * @param string $encoding The document encoding
     * @param string $type The document type
     *
     * @return \DiDom\Document
     */
    public static function create($string = null, $isFile = false, $encoding = 'UTF-8', $type = 'html')
    {
        return new Document($string, $isFile, $encoding, $type);
    }

    /**
     * Create new element node.
     *
     * @param string $name The tag name of the element
     * @param string $value The value of the element
     * @param array  $attributes The attributes of the element
     *
     * @return \DiDom\Element created element
     */
    public function createElement($name, $value = null, $attributes = [])
    {
        $node = $this->document->createElement($name);

        $element = new Element($node, $value, $attributes);

        return $element;
    }

    /**
     * Create new element node by CSS selector.
     *
     * @param string $selector
     * @param string $value
     * @param array $attributes
     *
     * @return \DiDom\Element
     */
    public function createElementBySelector($selector, $value = null, $attributes = [])
    {
        $segments = Query::getSegments($selector);

        $name = array_key_exists('tag', $segments) ? $segments['tag'] : 'div';

        if (array_key_exists('attributes', $segments)) {
            $attributes = array_merge($attributes, $segments['attributes']);
        }

        if (array_key_exists('id', $segments)) {
            $attributes['id'] = $segments['id'];
        }

        if (array_key_exists('classes', $segments)) {
            $attributes['class'] = implode(' ', $segments['classes']);
        }

        return $this->createElement($name, $value, $attributes);
    }

    /**
     * Add new child at the end of the children.
     * 
     * @param \DiDom\Element|\DOMNode|array $nodes The appended child
     *
     * @return \DiDom\Document
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

            Errors::disable();

            $cloned = $node->cloneNode(true);
            $newNode = $this->document->importNode($cloned, true);

            $this->document->appendChild($newNode);

            Errors::restore();
        }

        return $this;
    }

    /**
     * Set preserveWhiteSpace property.
     * 
     * @param bool $value
     * 
     * @return \DiDom\Document
     */
    public function preserveWhiteSpace($value = true)
    {
        if (!is_bool($value)) {
            throw new InvalidArgumentException(sprintf('%s expects parameter 1 to be boolean, %s given', __METHOD__, gettype($value)));
        }

        $this->document->preserveWhiteSpace = $value;

        return $this;
    }

    /**
     * Load HTML or XML.
     * 
     * @param string $string HTML or XML string or file path
     * @param bool   $isFile Indicates that in first parameter was passed to the file path
     * @param string $type Type of document
     * @param int    $options Additional parameters
     */
    public function load($string, $isFile = false, $type = 'html', $options = 0)
    {
        if (!is_string($string)) {
            throw new InvalidArgumentException(sprintf('%s expects parameter 1 to be string, %s given', __METHOD__, (is_object($string) ? get_class($string) : gettype($string))));
        }

        if (!in_array(strtolower($type), ['xml', 'html'])) {
            throw new InvalidArgumentException(sprintf('Document type must be "xml" or "html", %s given', __METHOD__, (is_object($type) ? get_class($type) : gettype($type))));
        }

        if (!is_integer($options)) {
            throw new InvalidArgumentException(sprintf('%s expects parameter 4 to be integer, %s given', __METHOD__, (is_object($options) ? get_class($options) : gettype($options))));
        }

        $string = trim($string);

        if ($isFile) {
            $string = $this->loadFile($string);
        }

        if (substr($string, 0, 5) !== '<?xml') {
            $prolog = sprintf('<?xml version="1.0" encoding="%s"?>', $this->document->encoding);

            $string = $prolog.$string;
        }

        $this->type = strtolower($type);

        Errors::disable();

        $this->type === 'xml' ? $this->document->loadXml($string, $options) : $this->document->loadHtml($string, $options);

        Errors::restore();

        return $this;
    }

    /**
     * Load HTML from a string.
     * 
     * @param string $html The HTML string
     * @param int    $options Additional parameters
     *
     * @return \DiDom\Document
     *
     * @throws \InvalidArgumentException if the provided argument is not a string
     */
    public function loadHtml($html, $options = 0)
    {
        return $this->load($html, false, 'html', $options);
    }

    /**
     * Load HTML from a file.
     * 
     * @param string $filepath The path to the HTML file
     * @param int    $options Additional parameters
     *
     * @return \DiDom\Document
     *
     * @throws \InvalidArgumentException if the file path is not a string
     * @throws \RuntimeException if the file does not exist
     * @throws \RuntimeException if you are unable to load the file
     */
    public function loadHtmlFile($filepath, $options = 0)
    {
        return $this->load($filepath, true, 'html', $options);
    }

    /**
     * Load XML from a string.
     * 
     * @param string $xml The XML string
     * @param int    $options Additional parameters
     *
     * @return \DiDom\Document
     *
     * @throws \InvalidArgumentException if the provided argument is not a string
     */
    public function loadXml($xml, $options = 0)
    {
        return $this->load($xml, false, 'xml', $options);
    }

    /**
     * Load XML from a file.
     * 
     * @param string $filepath The path to the XML file
     * @param int    $options Additional parameters
     *
     * @return \DiDom\Document
     *
     * @throws \InvalidArgumentException if the file path is not a string
     * @throws \RuntimeException if the file does not exist
     * @throws \RuntimeException if you are unable to load the file
     */
    public function loadXmlFile($filepath, $options = 0)
    {
        return $this->load($filepath, true, 'xml', $options);
    }

    /**
     * Reads entire file into a string.
     * 
     * @param string $filepath The path to the file
     *
     * @return strting
     *
     * @throws \InvalidArgumentException if the file path is not a string
     * @throws \RuntimeException if the file does not exist
     * @throws \RuntimeException if you are unable to load the file
     */
    protected function loadFile($filepath)
    {
        if (!is_string($filepath)) {
            throw new InvalidArgumentException(sprintf('%s expects parameter 1 to be string, %s given', __METHOD__, gettype($filepath)));
        }

        if (filter_var($filepath, FILTER_VALIDATE_URL) === false) {
            if (!file_exists($filepath)) {
                throw new RuntimeException(sprintf('File %s not found', $filepath));
            }
        }

        $content = file_get_contents($filepath);

        if ($content === false) {
            throw new RuntimeException(sprintf('Could not load file %s', $filepath));
        }

        return $content;
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
        $xpath = new DOMXPath($this->document);

        $expression = Query::compile($expression, $type);
        $expression = sprintf('count(%s) > 0', $expression);

        return $xpath->evaluate($expression);
    }

    /**
     * Searches for an node in the DOM tree for a given XPath expression or a CSS selector.
     * 
     * @param string $expression XPath expression or a CSS selector
     * @param string $type The type of the expression
     * @param bool   $wrapElement Returns array of \DiDom\Element if true, otherwise array of \DOMElement
     *
     * @return \DiDom\Element[]|\DOMElement[]
     */
    public function find($expression, $type = Query::TYPE_CSS, $wrapElement = true)
    {
        $expression = Query::compile($expression, $type);

        $xpath = new DOMXPath($this->document);

        $xpath->registerNamespace("php", "http://php.net/xpath");
        $xpath->registerPhpFunctions();

        $nodeList = $xpath->query($expression);

        $result = [];

        if ($wrapElement) {
            foreach ($nodeList as $node) {
                $result[] = $this->wrapNode($node);
            }
        } else {
            foreach ($nodeList as $node) {
                $result[] = $node;
            }
        }

        return $result;
    }

    /**
     * Searches for an node in the DOM tree and returns first element or null.
     * 
     * @param string $expression XPath expression or a CSS selector
     * @param string $type The type of the expression
     * @param bool   $wrapElement Returns \DiDom\Element if true, otherwise \DOMElement
     *
     * @return \DiDom\Element|\DOMElement|null
     */
    public function first($expression, $type = Query::TYPE_CSS, $wrapElement = true)
    {
        $nodes = $this->find($expression, $type, false);

        if (count($nodes) === 0) {
            return null;
        }

        return $wrapElement ? $this->wrapNode($nodes[0]) : $nodes[0];
    }

    protected function wrapNode($node)
    {
        switch (get_class($node)) {
            case 'DOMElement':
                return new Element($node);

            case 'DOMText':
                return $node->data;

            case 'DOMAttr':
                return $node->value;
        }

        throw new RuntimeException(sprintf('Unknown node type "%s"', get_class($node)));
    }

    /**
     * Searches for an node in the DOM tree for a given XPath expression.
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
     * Counts nodes for a given XPath expression or a CSS selector.
     * 
     * @param string $expression XPath expression or CSS selector
     * @param string $type The type of the expression
     *
     * @return int
     */
    public function count($expression, $type = Query::TYPE_CSS)
    {
        $xpath = new DOMXPath($this->document);

        $expression = Query::compile($expression, $type);
        $expression = sprintf('count(%s)', $expression);

        return $xpath->evaluate($expression);
    }

    /**
     * Dumps the internal document into a string using HTML formatting.
     * 
     * @param int $options Additional options
     * 
     * @return string The document html
     */
    public function html($options = LIBXML_NOEMPTYTAG)
    {
        return trim($this->document->saveXML($this->getElement(), $options));
    }

    /**
     * Dumps the internal document into a string using XML formatting.
     * 
     * @param int $options Additional options
     * 
     * @return string The document xml
     */
    public function xml($options = 0)
    {
        return trim($this->document->saveXML($this->document, $options));
    }

    /**
     * Nicely formats output with indentation and extra space.
     * 
     * @param bool $format Formats output if true
     *
     * @return \DiDom\Document
     */
    public function format($format = true)
    {
        if (!is_bool($format)) {
            throw new InvalidArgumentException(sprintf('%s expects parameter 1 to be boolean, %s given', __METHOD__, gettype($format)));
        }

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
     * @param Document|\DOMDocument $document The compared document
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
                throw new InvalidArgumentException(sprintf('Argument 1 passed to %s must be an instance of %s or DOMDocument, %s given', __METHOD__, __CLASS__, (is_object($document) ? get_class($document) : gettype($document))));
            }

            $element = $document->documentElement;
        }

        if ($element === null) {
            return false;
        }

        return $this->getElement()->isSameNode($element);
    }

    /**
     * Returns the type of document (XML or HTML).
     * 
     * @return string
     */
    public function getType()
    {
        return $this->type;
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
        if ($this->document->documentElement === null) {
            throw new RuntimeException('Cannot convert empty document to Element');
        }

        return new Element($this->document->documentElement);
    }

    /**
     * Convert the document to its string representation.
     * 
     * @return string
     */
    public function __toString()
    {
        return $this->type === 'xml' ? $this->xml() : $this->html();
    }

    /**
     * Searches for an node in the DOM tree for a given XPath expression or a CSS selector.
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
