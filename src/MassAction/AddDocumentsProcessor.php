<?php

namespace Pim\Bundle\TextmasterBundle\MassAction;

use Akeneo\Component\StorageUtils\Detacher\ObjectDetacherInterface;
use Doctrine\Common\Util\ClassUtils;
use Pim\Bundle\EnrichBundle\Connector\Processor\AbstractProcessor;
use Pim\Bundle\TextmasterBundle\Project\BuilderInterface;
use Pim\Component\Catalog\Model\ProductInterface;
use Pim\Component\Connector\Repository\JobConfigurationRepositoryInterface;

/**
 * Create TextMaster document from product
 * 
 * @author    Jean-Marie Leroux <jean-marie.leroux@akeneo.com>
 * @copyright 2016 TextMaster.com (https://textmaster.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class AddDocumentsProcessor extends AbstractProcessor
{
    /** @var BuilderInterface */
    protected $projectBuilder;

    /** @var ObjectDetacherInterface */
    protected $detacher;

    /**
     * {@inheritdoc}
     * @param BuilderInterface $projectBuilder
     * @param ObjectDetacherInterface $detacher
     */
    public function __construct(
        JobConfigurationRepositoryInterface $jobConfigurationRepo,
        BuilderInterface $projectBuilder,
        ObjectDetacherInterface $detacher
    )
    {
        parent::__construct($jobConfigurationRepo);
        $this->projectBuilder = $projectBuilder;
        $this->detacher = $detacher;
    }

    /**
     * @param ProductInterface $product
     *
     * @return array
     * @throws \Exception
     */
    public function process($product)
    {
        if (!$product instanceof ProductInterface) {
            throw new \Exception(
                sprintf('Processed item must implement ProductInterface, %s given', ClassUtils::getClass($product))
            );
        }

        $attributesToTranslate = $this->projectBuilder->createDocumentData($product, 'en_US');

        if (null === $attributesToTranslate) {
            $itemDetails = [
                'product identifier' => $product->getIdentifier()->getData(),
            ];
            $this->stepExecution->addWarning('skipped_document', 'no content to translate', [], $itemDetails);
        }

        $this->detacher->detach($product);

        return $attributesToTranslate;
    }
}
