<?php

declare(strict_types=1);

namespace DiDom;

use DOMDocumentFragment;

/**
 * @property string $tag
 */
class DocumentFragment extends Node
{
    /**
     * @param DOMDocumentFragment $documentFragment
     */
    public function __construct(DOMDocumentFragment $documentFragment)
    {
        $this->setNode($documentFragment);
    }

    /**
     * Append raw XML data.
     *
     * @param string $data
     */
    public function appendXml($data)
    {
        $this->node->appendXML($data);
    }
}
