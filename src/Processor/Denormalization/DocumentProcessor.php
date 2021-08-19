<?php

namespace Pim\Bundle\TextmasterBundle\Processor\Denormalization;

use Akeneo\Pim\Enrichment\Component\Product\Model\EntityWithFamilyVariantInterface;
use Akeneo\Pim\Enrichment\Component\Product\Model\EntityWithValuesInterface;
use Akeneo\Pim\Enrichment\Component\Product\Model\ProductInterface;
use Akeneo\Pim\Enrichment\Component\Product\Model\ProductModelInterface;
use Akeneo\Tool\Component\Batch\Item\ExecutionContext;
use Akeneo\Tool\Component\Batch\Item\ItemProcessorInterface;
use Akeneo\Tool\Component\Batch\Model\StepExecution;
use Akeneo\Tool\Component\Batch\Step\StepExecutionAwareInterface;
use Akeneo\Tool\Component\StorageUtils\Detacher\BulkObjectDetacherInterface;
use Akeneo\Tool\Component\StorageUtils\Detacher\ObjectDetacherInterface;
use Doctrine\Common\Util\ClassUtils;
use Exception;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Pim\Bundle\TextmasterBundle\Manager\DocumentManager;
use Pim\Bundle\TextmasterBundle\Manager\ProjectManager;
use Pim\Bundle\TextmasterBundle\Model\ProjectInterface;
use Pim\Bundle\TextmasterBundle\Builder\ProjectBuilderInterface;
use Pim\Bundle\TextmasterBundle\Provider\LocaleProvider;
use Pim\Bundle\TextmasterBundle\Step\PrepareProjectsStep;

/**
 * Class DocumentProcessor.
 *
 * @package Pim\Bundle\TextmasterBundle\Processor\Denormalization
 * @author  Jessy JURKOWSKI <jessy.jurkowski@cgi.com>
 */
class DocumentProcessor implements StepExecutionAwareInterface, ItemProcessorInterface
{
    protected const DOCUMENTS_TO_SAVE = 20;

    /** @var StepExecution */
    protected $stepExecution;

    /** @var LocaleProvider */
    protected $localeProvider;

    /** @var ProjectInterface */
    protected $projects;

    /** @var ProjectBuilderInterface */
    protected $projectBuilder;

    /** @var DocumentManager */
    protected $documentManager;

    /** @var ProjectManager */
    protected $projectManager;

    /** @var ObjectDetacherInterface|BulkObjectDetacherInterface */
    protected $objectDetacher;

    /** @var array */
    protected $availableAttributes = [];

    /**
     * DocumentProcessor constructor.
     *
     * @param LocaleProvider $localeProvider
     * @param ProjectBuilderInterface $projectBuilder
     * @param DocumentManager $documentManager
     * @param ProjectManager $projectManager
     * @param ObjectDetacherInterface $objectDetacher
     */
    public function __construct(
        LocaleProvider $localeProvider,
        ProjectBuilderInterface $projectBuilder,
        DocumentManager $documentManager,
        ProjectManager $projectManager,
        ObjectDetacherInterface $objectDetacher
    ) {
        $this->localeProvider  = $localeProvider;
        $this->projectBuilder  = $projectBuilder;
        $this->documentManager = $documentManager;
        $this->projectManager  = $projectManager;
        $this->objectDetacher  = $objectDetacher;
    }

    /**
     * @inheritdoc
     */
    public function process($product)
    {
        if (!$product instanceof ProductInterface && !$product instanceof ProductModelInterface) {
            $this->removePreparedProjects();
            throw new Exception(
                sprintf(
                    'Processed item must implement ProductInterface or Product Model, %s given',
                    ClassUtils::getClass($product)
                )
            );
        }

        $documents = [];
        $attributeCodesToTranslate = $this->getAttributesToTranslate($product);
        $dateRangeStartsAt = $this->getJobContext()->get(PrepareProjectsStep::DATE_RANGE_STARTS_AT_CONTEXT_KEY);
        $dateRangeEndsAt = $this->getJobContext()->get(PrepareProjectsStep::DATE_RANGE_ENDS_AT_CONTEXT_KEY);

        /** @var  $project ProjectInterface */
        foreach ($this->getProjects() as $project) {
            $apiTemplate = $this->getApiTemplateById(
                $project->getApiTemplateId()
            );

            if (null === $apiTemplate) {
                continue;
            }

            $localeFrom = $this->localeProvider->getPimLocaleCode($apiTemplate['language_from']);
            $dataToSend = $this->projectBuilder->createDocumentData($product, $attributeCodesToTranslate, $localeFrom, $dateRangeStartsAt, $dateRangeEndsAt);

            if (null === $dataToSend) {
                continue;
            }

            $documents[] = $this->documentManager->createDocument(
                $project->getId(),
                $product->getId(),
                $product instanceof ProductInterface ? $product->getIdentifier() : $product->getCode(),
                $product->getLabel($localeFrom),
                json_encode($dataToSend),
                $apiTemplate['language_from'],
                $apiTemplate['language_to']
            );

            if (count($documents) >= self::DOCUMENTS_TO_SAVE) {
                $this->saveDocuments($documents);
                $this->stepExecution->incrementSummaryInfo('documents_added', self::DOCUMENTS_TO_SAVE);
                $documents = [];
            }
        }

        if (count($documents) > 0) {
            $this->saveDocuments($documents);
            $this->stepExecution->incrementSummaryInfo('documents_added', count($documents));
        }

        $this->objectDetacher->detach($product);
    }

    /**
     * @param array DocumentInterface[]
     *
     * @throws Exception
     */
    protected function saveDocuments(array $documents): void
    {
        try {
            $this->documentManager->saveDocuments($documents);
            $this->objectDetacher->detachAll($documents);
        } catch (Exception $exception) {
            $this->removePreparedProjects();
            throw $exception;
        }
    }

    /**
     * @return int
     */
    protected function removePreparedProjects(): int
    {
        $projectsIds = array_keys($this->getProjects());
        $this->documentManager->deleteDocumentsByProjectIds($projectsIds);

        return $this->projectManager->deleteProjectsByIds($projectsIds);
    }

    /**
     * @param StepExecution $stepExecution
     */
    public function setStepExecution(StepExecution $stepExecution)
    {
        $this->stepExecution = $stepExecution;
    }

    /**
     * @return ProjectInterface[]
     */
    protected function getProjects(): array
    {
        return (array)$this->getJobContext()->get(PrepareProjectsStep::PROJECTS_CONTEXT_KEY);
    }

    /**
     * Retrieve api template informations.
     *
     * @param string $apiTemplateId
     *
     * @return array
     */
    protected function getApiTemplateById(string $apiTemplateId): array
    {
        return $this->getJobContext()->get(PrepareProjectsStep::API_TEMPLATES_CONTEXT_KEY)[$apiTemplateId];
    }

    /**
     * @return ExecutionContext
     */
    protected function getJobContext()
    {
        return $this->stepExecution->getJobExecution()->getExecutionContext();
    }

    /**
     * Retrieve available attribute codes.
     *
     * @param EntityWithValuesInterface $product
     *
     * @return array
     */
    protected function getAttributesToTranslate(EntityWithValuesInterface $product): array
    {
        $selectedAttributeCodes = $this->getJobContext()->get(PrepareProjectsStep::SELECTED_ATTRIBUTES_CONTEXT_KEY);
        $availableAttributes = array_intersect($selectedAttributeCodes, $product->getUsedAttributeCodes());

        if ($product instanceof ProductModelInterface) {
            $familyVariantCode = $product->getFamilyVariant()->getCode();

            if (EntityWithFamilyVariantInterface::ROOT_VARIATION_LEVEL === $product->getLevel()) {
                $this->availableAttributes[$familyVariantCode] = $product->getUsedAttributeCodes();
            } else {
                if (!isset($this->availableAttributes[$familyVariantCode])) {
                    $this->availableAttributes[$familyVariantCode] = $this->getAttributesToTranslate(
                        $product->getParent()
                    );

                    $this->objectDetacher->detach($product->getParent());
                }

                $availableAttributes = array_diff(
                    $availableAttributes,
                    $this->availableAttributes[$familyVariantCode]
                );

                $parentAttributes = [];
                if ($product->getParent() && $product->getParent() instanceof ProductModelInterface) {
                    $parentAttributes = $this->getAttributesToTranslate($product->getParent());
                }

                // Only get attributes that different with parent
                $availableAttributes = array_diff(
                    $availableAttributes,
                    $parentAttributes
                );
            }
        } else {
            $parent = $product->getParent();
            $rootParent = $parent && $parent instanceof ProductModelInterface ? $parent->getParent() : null;
            $parentAttributes = $this->getAttributesToTranslate($parent);
            $rootParentAttributes = [];

            if ($rootParent !== null && $rootParent instanceof ProductModelInterface) {
                $rootParentAttributes = $this->getAttributesToTranslate($rootParent);
            }

            // Only get attributes that different with parent and root parent
            $availableAttributes = array_diff(
                $availableAttributes,
                $parentAttributes,
                $rootParentAttributes
            );
        }

        return $availableAttributes;
    }
}
