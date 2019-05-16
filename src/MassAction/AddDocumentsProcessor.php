<?php

namespace Pim\Bundle\TextmasterBundle\MassAction;

use Akeneo\Tool\Component\Batch\Item\ExecutionContext;
use Akeneo\Tool\Component\StorageUtils\Detacher\ObjectDetacherInterface;
use Doctrine\Common\Util\ClassUtils;
use Akeneo\Pim\Enrichment\Component\Product\Connector\Processor\MassEdit\AbstractProcessor;
use Pim\Bundle\TextmasterBundle\Api\WebApiRepository;
use Pim\Bundle\TextmasterBundle\Locale\LocaleFinderInterface;
use Pim\Bundle\TextmasterBundle\Project\BuilderInterface;
use Pim\Bundle\TextmasterBundle\Project\ProjectInterface;
use Akeneo\Pim\Enrichment\Component\Product\Model\ProductInterface;
use Akeneo\Pim\Enrichment\Component\Product\Model\ProductModel;
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
        $this->detacher       = $detacher;
        $this->apiRepository  = $apiRepository;
        $this->logger         = $logger;
        $this->localeFinder   = $localeFinder;
    }

    /**
     * @param ProductInterface $product
     *
     * @return array
     * @throws \Exception
     */
    public function process($product)
    {
        if (!$product instanceof ProductInterface && !$product instanceof ProductModel) {
            throw new \Exception(
                sprintf(
                    'Processed item must implement ProductInterface or Product Model, %s given',
                    ClassUtils::getClass($product)
                )
            );
        }

        $projects      = $this->getProjects();
        $apiTemmplates = $this->apiRepository->getApiTemplates();

        foreach ($projects as $project) {
            $this->logger->debug(sprintf('Processing project %s', $project->getCode()));
            $apiTemplate = $apiTemmplates[$project->getApiTemplateId()];
            $fromLocale  = $this->localeFinder->getPimLocaleCode($apiTemplate['language_from']);
            $this->logger->debug(sprintf('API template: %s', json_encode($apiTemplate)));
            $this->logger->debug(sprintf('PIM locale code: %s', $fromLocale));
            $attributesToTranslate = $this->projectBuilder->createDocumentData($product, $fromLocale);

            if (null === $attributesToTranslate) {
                $this->stepExecution->incrementSummaryInfo('no_translation');
            } else {
                $project->addDocument($attributesToTranslate);
                $this->stepExecution->incrementSummaryInfo('documents_added');
            }

            if (!is_array($project->getDocuments())) {
                var_dump($product->getIdentifier());
            }

            if (null !== $project->getDocuments()) {
                $this->logger->debug(
                    sprintf('Add %d documents to project %s', count($project->getDocuments()), $project->getCode())
                );
            }
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
