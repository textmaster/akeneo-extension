<?php

namespace Pim\Bundle\TextmasterBundle\Product;

use Pim\Bundle\CatalogBundle\Repository\ProductRepositoryInterface;
use Pim\Component\Catalog\Model\ProductInterface;
use Pim\Component\Catalog\Updater\ProductUpdater;
use Textmaster\Exception\RuntimeException;
use Textmaster\Model\DocumentInterface;

/**
 * Update Akeneo product from Textmaster data
 *
 * @author    Jean-Marie Leroux <jean-marie.leroux@akeneo.com>
 * @copyright 2016 TextMaster.com (https://textmaster.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Updater implements UpdaterInterface
{
    /** @var ProductRepositoryInterface */
    protected $productRepository;

    /** @var ProductUpdater */
    protected $productUpdater;

    /**
     * @param ProductRepositoryInterface $productRepository
     * @param ProductUpdater             $productUpdater
     */
    public function __construct(ProductRepositoryInterface $productRepository, ProductUpdater $productUpdater)
    {
        $this->productRepository = $productRepository;
        $this->productUpdater = $productUpdater;
    }

    /**
     * {@inheritdoc}
     */
    public function update(DocumentInterface $document, $localeCode)
    {
        $product = $this->findRelatedProduct($document);

        $data = [];
        foreach ($document->getSourceContent() as $key => $content) {
            list($attributeCode, $channelCode) = $this->extractAttributeAndChannel($key);
            $data[$attributeCode][] = [
                'locale' => $localeCode,
                'scope'  => $channelCode,
                'data'   => $content,
            ];
        }
        $this->productUpdater->update($product, $data);

        return $product;
    }

    /**
     * Find product from a document
     *
     * @param DocumentInterface $document
     *
     * @return null|ProductInterface
     */
    protected function findRelatedProduct(DocumentInterface $document)
    {
        $sku = $this->extractIdentifier($document);
        $repo = $this->productRepository;

        return $repo->findOneByIdentifier($sku);
    }

    /**
     * Extract product SKU from document title
     *
     * @param DocumentInterface $document
     *
     * @return string
     */
    protected function extractIdentifier(DocumentInterface $document)
    {
        return $document->getTitle();
    }

    /**
     * Extract attribute code and locale from a document key
     *
     * @param string $textmasterKey
     *
     * @return string[]
     */
    protected function extractAttributeAndChannel($textmasterKey)
    {
        if (!preg_match('/^([^-]+)(?:-([^-]+))?$/', $textmasterKey, $matches)) {
            throw new RuntimeException(
                sprintf('Cannot extract attribute code and channel from key %s', $textmasterKey)
            );
        }
        $attributeCode = $matches[1];
        $channelCode = isset($matches[2]) ? $matches[2] : null;

        return [
            $attributeCode,
            $channelCode,
        ];
    }
}
