<?php

namespace Pim\Bundle\TextmasterBundle\Updater;

use Akeneo\Component\StorageUtils\Updater\ObjectUpdaterInterface;
use Doctrine\Common\Persistence\ObjectRepository;
use Pim\Component\Catalog\Model\ProductInterface;
use Pim\Component\Catalog\Model\ProductModelInterface;
use Textmaster\Exception\RuntimeException;
use Textmaster\Model\DocumentInterface;

/**
 * Class AbstractUpdater.
 *
 * @package Pim\Bundle\TextmasterBundle\Updater
 * @author  Jessy JURKOWSKI <jessy.jurkowski@cgi.com>
 */
abstract class AbstractUpdater implements UpdaterInterface
{
    /** @var ObjectRepository */
    protected $repository;

    /** @var ObjectUpdaterInterface */
    protected $updater;

    /**
     * AbstractUpdater constructor.
     *
     * @param ObjectRepository       $repository
     * @param ObjectUpdaterInterface $updater
     */
    public function __construct(ObjectRepository $repository, ObjectUpdaterInterface $updater)
    {
        $this->repository = $repository;
        $this->updater    = $updater;
    }

    /**
     * {@inheritdoc}
     */
    public function update(DocumentInterface $document, $localeCode)
    {
        $data = [];
        $product = $this->findRelatedProduct($document);

        foreach ($document->getSourceContent() as $key => $content) {
            list($attributeCode, $channelCode) = $this->extractAttributeAndChannel($key);

            $data[$attributeCode][] = [
                'locale' => $localeCode,
                'scope'  => $channelCode,
                'data'   => $content,
            ];
        }

        $this->updater->update($product, ['values' => $data]);

        return $product;
    }

    /**
     * Retrieve product or product model identifier from document.
     *
     * @param DocumentInterface $document
     *
     * @return string
     */
    abstract protected function extractIdentifier(DocumentInterface $document): string;

    /**
     * Find product or product model from document.
     *
     * @param DocumentInterface $document
     *
     * @return null|ProductInterface|ProductModelInterface
     */
    protected function findRelatedProduct(DocumentInterface $document)
    {
        $sku = $this->extractIdentifier($document);

        return $this->repository->findOneByIdentifier($sku);
    }

    /**
     * Extract attribute code and locale from a document key
     *
     * @param string $textmasterKey
     *
     * @return string[]
     */
    protected function extractAttributeAndChannel(string $textmasterKey): array
    {
        if (!preg_match('/^([^-]+)(?:-([^-]+))?$/', $textmasterKey, $matches)) {
            throw new RuntimeException(
                sprintf('Cannot extract attribute code and channel from key %s', $textmasterKey)
            );
        }
        $attributeCode = $matches[1];
        $channelCode   = isset($matches[2]) ? $matches[2] : null;

        return [
            $attributeCode,
            $channelCode,
        ];
    }
}
