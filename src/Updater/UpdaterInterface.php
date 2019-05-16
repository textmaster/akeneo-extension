<?php

namespace Pim\Bundle\TextmasterBundle\Updater;

use Akeneo\Pim\Enrichment\Component\Product\Model\ProductInterface;
use Textmaster\Model\DocumentInterface;

/**
 * Update Akeneo product from Textmaster data
 *
 * @author    Jean-Marie Leroux <jean-marie.leroux@akeneo.com>
 * @copyright 2016 TextMaster.com (https://textmaster.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

interface UpdaterInterface
{
    /**
     * Update a product from a document
     *
     * @param DocumentInterface $document
     * @param                   $localeCode
     *
     * @return null|ProductInterface
     */
    public function update(DocumentInterface $document, $localeCode);
}
