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
     * @var string
     */
    protected $type;

    /**
     * Constructor.
     * 
     * @param  string $string HTML or XML string or file path
     * @param  bool   $isFile indicates that in first parameter was passed to the file path
     * @param  string $encoding The document encoding
     * @param  string $type The document type
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

        if ($string !== null) {
            $this->load($string, $isFile, $type);
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
     * Load HTML or XML.
     * 
     * @param  string $string HTML or XML string or file path
     * @param  bool   $isFile indicates that in first parameter was passed to the file path
     * @param  string $type Type of document
     */
    public function load($string, $isFile = false, $type = 'html')
    {
        if (!is_string($string)) {
            throw new InvalidArgumentException(sprintf('%s expects parameter 1 to be string, %s given', __METHOD__, (is_object($string) ? get_class($string) : gettype($string))));
        }

        if ($isFile) {
            $string = $this->loadFile($string);
        }

        if (!is_string($string) or !in_array(strtolower($type), ['xml', 'html'])) {
            throw new InvalidArgumentException(sprintf('Document type must be "xml" or "html", %s given', __METHOD__, (is_object($type) ? get_class($type) : gettype($type))));
        }

        if (substr($string, 0, 5) !== '<?xml') {
            $prolog = sprintf('<?xml encoding="%s">', $this->document->encoding);
            $string = $prolog.$string;
        }

        $this->type = strtolower($type);

        libxml_use_internal_errors(true);
        libxml_disable_entity_loader(true);

        $this->type === 'xml' ? $this->document->loadXml($string) : $this->document->loadHtml($string);

        libxml_clear_errors();

        libxml_disable_entity_loader(false);
        libxml_use_internal_errors(false);

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
        return $this->load($html, false, 'html');
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
        return $this->load($filepath, true, 'html');
    }

    /**
     * Load XML from a string.
     * 
     * @param  string $xml The XML string
     *
     * @return \DiDom\Document
     *
     * @throws \InvalidArgumentException if the provided argument is not a string
     */
    public function loadXml($xml)
    {
        return $this->load($xml, false, 'xml');
    }

    /**
     * Load XML from a file.
     * 
     * @param  string $filepath The path to the XML file
     *
     * @return \DiDom\Document
     *
     * @throws \InvalidArgumentException if the file path is not a string
     * @throws \RuntimeException if the file does not exist
     * @throws \RuntimeException if you are unable to load the file
     */
    public function loadXmlFile($filepath)
    {
        return $this->load($filepath, true, 'xml');
    }

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
                $elements[] = $node instanceof \DOMElement ?
                    new Element($node) :
                    new Attribute($node);
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
     * Dumps the internal document into a string using XML formatting.
     * 
     * @return string The document html
     */
    public function xml()
    {
        return trim($this->document->saveXML());
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
        return new Element($this->getElement());
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
