<?php

namespace DiDom;

/**
 * @todo create interface for attribute classes
 */
class Attribute extends Node
{
    /**
     * @var \DOMAttr
     */
    protected $node;
    /**
     * @var Element
     */
    protected $ownerElement;

    /**
     * Constructor
     *
     * @param \DOMAttr $domAttr
     */
    public function __construct(\DOMAttr $domAttr)
    {
        parent::__construct($domAttr);
    }

    /**
     * Returns owner element for this node
     *
     * @return Element
     */
    public function getOwner()
    {
        if (!$this->ownerElement && $this->node->ownerElement)
            $this->ownerElement = new Element($this->node->ownerElement);

        return $this->ownerElement;
    }

    /**
     * Returns the text content of this node
     *
     * @return string
     */
    public function text()
    {
        return $this->node->value;
    }
}