<?php

namespace Pim\Bundle\TextmasterBundle\Project\Model;

use Textmaster\Model\Document as ParentDocument;

/**
 * Class Document.
 * This class was "override" to avoid warning.
 *
 * @package Pim\Bundle\TextmasterBundle\Project\Model
 * @author  Jessy JURKOWSKI <jessy.jurkowski@cgi.com>
 */
class Document extends ParentDocument
{
    /**
     * Format translated content to an array 'property' => 'value'.
     * This methode was override to avoid warning on not set index in original content.
     *
     * @return array
     */
    protected function formatTranslatedContent()
    {
        $data = [];
        foreach ($this->getOriginalContent() as $property => $value) {
            if (isset($value['completed_phrase'])) {
                $data[$property] = $value['completed_phrase'];
            }
        }

        return $data;
    }
}
