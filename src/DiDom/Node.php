<?php

namespace DiDom;

/**
 * Base class for all nodes
 */
abstract class Node implements NodeInterface
{
    /**
     * @var \DOMNode
     */
    protected $node;

    /**
     * Constructor
     *
     * @param \DOMNode $domNode
     */
    public function __construct(\DOMNode $domNode)
    {
        $this->node = $domNode;
    }

    /**
     * Returns the name of this node
     *
     * @return string
     */
    public function name()
    {
        return $this->node->nodeName;
    }

    /**
     * Returns the text content of this node
     *
     * @return string
     */
    public function text()
    {
        return $this->node->textContent;
    }
}