<?php

namespace DiDom;

/**
 * Interface for all nodes
 */
interface NodeInterface
{
    /**
     * Returns the name of this node
     *
     * @return string
     */
    public function name();

    /**
     * Returns the text content of this node
     *
     * @return string
     */
    public function text();
}