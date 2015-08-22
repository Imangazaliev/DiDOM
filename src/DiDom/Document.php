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
    protected $document = null;

    /**
     * Constructor.
     * 
     * @param  string $html HTML code or file path
     * @param  bool   $isFile indicates that in first parameter was passed to the file path
     * @return void
     */
    public function __construct($html = null, $isFile = false)
    {
        $this->document = new DOMDocument();

        if ($html) {
            if ($isFile) {
                $this->loadHtmlFile($html);
            } else {
                $this->loadHtml($html);
            }
        }
    }

    /**
     * @param  string $name
     * @param  string $value
     * @return \DiDom\Element
     */
    public function createElement($name, $value = '')
    {
        $domElement = $this->document->createElement($name, $value);

        return new Element($domElement);
    }

    /**
     * @param  \DiDom\Element|\DOMNode $element
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function appendChild($element)
    {
        if ($element instanceof Element) {
            $element = $element->getElement();
        }

        if (!$element instanceof DOMNode) {
            throw new InvalidArgumentException(sprintf('Argument 1 passed to %s must be an instance of %s, %s given', __METHOD__, 'DOMNode', gettype($html)));
        }

        $cloned = $element->cloneNode(true);
        $temp   = $this->document->importNode($cloned, true);

        $this->document->appendChild($temp);

        return $this;
    }

    /**
     * @param  string $html
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function loadHtml($html)
    {
        if (!is_string($html)) {
            throw new InvalidArgumentException(sprintf('%s expects parameter 1 to be string, %s given', __METHOD__, gettype($html)));
        }

        libxml_use_internal_errors(true);
        libxml_disable_entity_loader(true);

        $this->document->loadHtml($html);

        libxml_clear_errors();

        libxml_disable_entity_loader(false);
        libxml_use_internal_errors(false);

        return $this;
    }

    /**
     * @param  string $filepath
     * @return $this
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
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
     * Checks for the item.
     * 
     * @param  string $expression XPath expression or CSS selector
     * @param  string $type the type of the expression
     * @return bool
     */
    public function has($expression, $type = Query::TYPE_CSS)
    {
        return count($this->find($expression, $type)) > 0;
    }

    /**
     * Searches for the element in the DOM tree.
     * 
     * @param  string $expression XPath expression or CSS selector
     * @param  string $type the type of the expression
     * @return array
     */
    public function find($expression, $type = Query::TYPE_CSS)
    {
        $expression = Query::compile($expression, $type);

        $xpath    = new DOMXPath($this->document);
        $nodeList = $xpath->query($expression);
        $elements = array();

        foreach ($nodeList as $node) {
            $elements[] = new Element($node);
        }
        
        return $elements;
    }

    /**
     * @param  string $expression XPath expression
     * @return array
     */
    public function xpath($expression)
    {
        return $this->find($expression, Query::TYPE_XPATH);
    }

    /**
     * Dumps the internal document into a string using HTML formatting.
     * 
     * @return string
     */
    public function html()
    {
        return trim($this->document->saveHtml());
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
     * @return string
     */
    public function __toString()
    {
        return $this->html();
    }

    /**
     * @param  string $expression
     * @param  string $type
     * @return mixed
     */
    public function __invoke($expression, $type = Query::TYPE_CSS)
    {
        return $this->find($expression, $type);
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
}
