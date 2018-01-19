<?php

namespace Pim\Bundle\TextmasterBundle\MassAction;

use Akeneo\Component\Batch\Item\DataInvalidItem;
use Akeneo\Component\Batch\Item\ExecutionContext;
use Akeneo\Component\StorageUtils\Detacher\ObjectDetacherInterface;
use Doctrine\Common\Util\ClassUtils;
use Pim\Bundle\EnrichBundle\Connector\Processor\AbstractProcessor;
use Pim\Bundle\TextmasterBundle\Api\WebApiRepository;
use Pim\Bundle\TextmasterBundle\Locale\LocaleFinderInterface;
use Pim\Bundle\TextmasterBundle\Project\BuilderInterface;
use Pim\Bundle\TextmasterBundle\Project\ProjectInterface;
use Pim\Component\Catalog\Model\ProductInterface;
use Psr\Log\LoggerInterface;

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

    /** @var WebApiRepository */
    protected $apiRepository;

    /** @var LocaleFinderInterface */
    protected $localeFinder;

    /** @var LoggerInterface */
    protected $logger;

    public function __construct(
        BuilderInterface $projectBuilder,
        ObjectDetacherInterface $detacher,
        WebApiRepository $apiRepository,
        LocaleFinderInterface $localeFinder,
        LoggerInterface $logger
    ) {
        $this->projectBuilder = $projectBuilder;
        $this->detacher = $detacher;
        $this->apiRepository = $apiRepository;
        $this->logger = $logger;
        $this->localeFinder = $localeFinder;
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

        $projects = $this->getProjects();
        $apiTemmplates = $this->apiRepository->getApiTemplates();

        foreach ($projects as $project) {
            $this->logger->debug(sprintf('Processing project %s', $project->getCode()));
            $apiTemplate = $apiTemmplates[$project->getApiTemplateId()];
            $fromLocale = $this->localeFinder->getPimLocaleCode($apiTemplate['language_from']);
            $this->logger->debug(sprintf('API template: %s', json_encode($apiTemplate)));
            $this->logger->debug(sprintf('PIM locale code: %s', $fromLocale));
            $attributesToTranslate = $this->projectBuilder->createDocumentData($product, $fromLocale);

            if (null === $attributesToTranslate) {
                $invalidItem = new DataInvalidItem([
                    'product identifier' => $product->getIdentifier()->getData(),
                ]);
                $this->stepExecution->addWarning('No content to translate for product', [], $invalidItem);
            } else {
                $project->addDocument($attributesToTranslate);
                $this->stepExecution->incrementSummaryInfo('documents_added', 1);
            }
            $this->logger->debug(
                sprintf('Add %d documents to project %s', count($project->getDocuments()), $project->getCode())
            );
        }

        $this->detacher->detach($product);

        return $projects;
    }

    /**
     * @return ExecutionContext
     */
    protected function getJobContext()
    {
        return $this->stepExecution->getJobExecution()->getExecutionContext();
    }

    /**
     * @return ProjectInterface[]
     */
    protected function getProjects()
    {
        return (array)$this->getJobContext()->get(CreateProjectsTasklet::PROJECTS_CONTEXT_KEY);
    }
}
