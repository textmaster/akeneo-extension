<?php

namespace Pim\Bundle\TextmasterBundle\Updater;

use Textmaster\Model\DocumentInterface;

/**
 * Class ProductUpdater.
 *
 * @package Pim\Bundle\TextmasterBundle\Updater
 * @author  Jessy JURKOWSKI <jessy.jurkowski@cgi.com>
 */
class ProductUpdater extends AbstractUpdater
{
    /**
     * Retrieve product or product model identifier from document.
     *
     * @param DocumentInterface $document
     *
     * @return string
     */
    protected function extractIdentifier(DocumentInterface $document): string
    {
        return $document->getTitle();
    }
}
