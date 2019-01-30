<?php

namespace Pim\Bundle\TextmasterBundle\MassAction;

use Akeneo\Component\Batch\Item\ExecutionContext;
use Akeneo\Component\Batch\Item\FileInvalidItem;
use Akeneo\Component\Batch\Item\InvalidItemException;
use Akeneo\Component\Batch\Model\StepExecution;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Pim\Bundle\TextmasterBundle\Api\WebApiRepository;
use Pim\Bundle\TextmasterBundle\Project\ProjectInterface;
use Pim\Bundle\TextmasterBundle\Project\ProjectRepository;
use Pim\Component\Connector\Step\TaskletInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Textmaster\Model\DocumentInterface;

/**
 * Finalize the mass action:
 * - translation memory
 * - autolaunch
 *
 * @author    Jean-Marie Leroux <jean-marie.leroux@akeneo.com>
 * @copyright 2016 TextMaster.com (https://textmaster.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class FinalizeProjectsTasklet implements TaskletInterface
{
    private const STATUS_MAX_TRY = 25;

    /** @var StepExecution */
    protected $stepExecution;

    /** @var WebApiRepository */
    protected $apiRepository;

    /** @var ProjectRepository */
    protected $projectRepository;

    /** @var ConfigManager */
    protected $configManager;

    /** @var TranslatorInterface */
    protected $translator;

    /**
     * @param WebApiRepository    $apiRepository
     * @param ProjectRepository   $projectRepository
     * @param ConfigManager       $configManager
     * @param TranslatorInterface $translator
     */
    public function __construct(
        WebApiRepository $apiRepository,
        ProjectRepository $projectRepository,
        ConfigManager $configManager,
        TranslatorInterface $translator
    ) {
        $this->apiRepository     = $apiRepository;
        $this->projectRepository = $projectRepository;
        $this->configManager     = $configManager;
        $this->translator        = $translator;
    }

    /**
     * @inheritdoc
     */
    public function getConfigurationFields(): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function setStepExecution(StepExecution $stepExecution)
    {
        $this->stepExecution = $stepExecution;
    }

    /**
     * @inheritdoc
     * @throws InvalidItemException
     */
    public function execute()
    {
        $projects         = $this->getProjects();
        $canceledProjects = [];

        foreach ($projects as $project) {
            $this->waitForStatus($project, \Textmaster\Model\ProjectInterface::STATUS_IN_CREATION);

            if ($this->waitForDocumentsCounted($project)) {
                $this->apiRepository->finalizeProject($project->getCode());
            } else {
                $this->apiRepository->cancelProject($project->getCode());
                $canceledProjects[] = $project->getCode();
            }
        }

        if (0 < \count($canceledProjects)) {
            $this->projectRepository->removeProjectsByCode($canceledProjects);
        }

        $this->finalizeTasklet();
    }

    /**
     * Wait for a requested status
     *
     * @param ProjectInterface $project
     * @param string           $status
     *
     * @return bool
     */
    protected function waitForStatus(ProjectInterface $project, $status): bool
    {
        $retry = 0;

        while ($retry <= self::STATUS_MAX_TRY) {
            $textMasterproject = $this->apiRepository->getProject($project->getCode());
            if ($status === $textMasterproject->getStatus()) {
                return true;
            }
            sleep(5);
            $retry++;
        }

        return false;
    }

    /**
     * Retrieve all documents with status "in creation" from project.
     *
     * @param ProjectInterface $project
     *
     * @return DocumentInterface[]
     */
    protected function getCreatedDocuments(ProjectInterface $project): array
    {
        return $this->apiRepository->getAllDocuments(
            [
                'status' => [
                    '$in' => [
                        DocumentInterface::STATUS_IN_CREATION,
                    ],
                ]
            ],
            $project->getCode()
        );
    }

    /**
     * Wait for documents counted
     *
     * @param ProjectInterface $project
     *
     * @return bool
     */
    protected function waitForDocumentsCounted(ProjectInterface $project): bool
    {
        $retry = 0;

        while ($retry <= self::STATUS_MAX_TRY) {
            $apiDocuments = $this->getCreatedDocuments($project);

            if (true === $this->checkDocumentsCounted($apiDocuments)) {
                return true;
            }

            sleep(5);
            $retry++;
        }

        $this->stepExecution->addError(
            $this->translator->trans(
                'textmaster.customer.wordcount_error',
                ['%project_code%' => $project->getCode()]
            )
        );

        return false;
    }

    /**
     * Check if all documents are counted.
     *
     * @param array $apiDocuments
     *
     * @return bool
     */
    protected function checkDocumentsCounted(array $apiDocuments): bool
    {
        /** @var DocumentInterface $document */
        foreach ($apiDocuments as $document) {
            if (null === $document->getWordCount()) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return ProjectInterface[]
     */
    protected function getProjects(): array
    {
        return (array)$this->getJobContext()->get(CreateProjectsTasklet::PROJECTS_CONTEXT_KEY);
    }

    /**
     * @return ExecutionContext
     */
    protected function getJobContext(): ExecutionContext
    {
        return $this->stepExecution->getJobExecution()->getExecutionContext();
    }

    /**
     * Finalize tasklet by throw invalid item exception in case of errors.
     *
     * @throws InvalidItemException
     */
    protected function finalizeTasklet(): void
    {
        $this->stepExecution->addSummaryInfo('link', $this->translator->trans('textmaster.customer.validation_link'));

        $countErrors = \count($this->stepExecution->getErrors());

        if (0 < $countErrors) {
            $this->stepExecution->incrementSummaryInfo('skip', $countErrors);
            $itemPosition = $this->stepExecution->getSummaryInfo('item_position');

            $invalidItem = new FileInvalidItem([], $itemPosition);

            throw new InvalidItemException('', $invalidItem, [], 0);
        }
    }
}
