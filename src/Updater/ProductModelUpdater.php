<?php

namespace Pim\Bundle\TextmasterBundle\Updater;

use Textmaster\Model\DocumentInterface;

/**
 * Class ProductModelUpdater.
 *
 * @package Pim\Bundle\TextmasterBundle\Updater
 * @author  Jessy JURKOWSKI <jessy.jurkowski@cgi.com>
 */
class ProductModelUpdater extends AbstractUpdater
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
        $result = explode('product_model|', $document->getTitle());

        return $result[1];
    }
}
