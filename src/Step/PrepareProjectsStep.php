<?php

namespace Pim\Bundle\TextmasterBundle\Step;

use Akeneo\Tool\Component\Batch\Item\ExecutionContext;
use Akeneo\Tool\Component\Batch\Job\JobRepositoryInterface;
use Akeneo\Tool\Component\Batch\Model\StepExecution;
use Akeneo\Tool\Component\Batch\Step\AbstractStep;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Pim\Bundle\TextmasterBundle\Api\WebApiRepositoryInterface;
use Pim\Bundle\TextmasterBundle\Manager\ProjectManager;
use Pim\Bundle\TextmasterBundle\Model\ProjectCreationFormData;
use Pim\Bundle\TextmasterBundle\Model\ProjectInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Textmaster\Exception\InvalidArgumentException;

/**
 * @author  Jessy JURKOWSKI <jessy.jurkowski@cgi.com>
 * @author  Huy Nguyen <khnguyen@clever-age.com>
 */
class PrepareProjectsStep extends AbstractStep
{
    public const PROJECTS_CONTEXT_KEY = 'textmaster_projects';
    public const API_TEMPLATES_CONTEXT_KEY = 'textmaster_api_template';
    public const SELECTED_ATTRIBUTES_CONTEXT_KEY = 'textmaster_selected_attributes';
    public const DATE_RANGE_STARTS_AT_CONTEXT_KEY = 'textmaster_date_range_starts_at';
    public const DATE_RANGE_ENDS_AT_CONTEXT_KEY = 'textmaster_date_range_ends_at';

    /** @var WebApiRepositoryInterface */
    protected $webApiRepository;

    /** @var ProjectManager */
    protected $projectManager;

    /** @var ConfigManager */
    private $configManager;

    public function __construct(
        $name,
        EventDispatcherInterface $eventDispatcher,
        JobRepositoryInterface $jobRepository,
        ConfigManager $configManager,
        WebApiRepositoryInterface $webApiRepository,
        ProjectManager $projectManager
    ) {
        parent::__construct($name, $eventDispatcher, $jobRepository);

        $this->configManager = $configManager;
        $this->webApiRepository = $webApiRepository;
        $this->projectManager = $projectManager;
    }

    /**
     * @inheritdoc
     */
    protected function doExecute(StepExecution $stepExecution)
    {
        $actions = $this->getConfiguredActions($stepExecution);

        if (null === $actions) {
            $stepExecution->addError('No actions found.');

            return;
        }

        $formData = $this->getProjectCreationFormData($actions);
        $this->validateProjectCreationFormData($formData);

        $projects = [];

        foreach ($formData->getApiTemplateIds() as $apiTemplateId) {
            $projects[] = $this->projectManager->createProject(
                $formData->getUsername(),
                $formData->getProjectName(),
                $apiTemplateId
            );
        }

        $this->projectManager->saveProjects($projects);

        $projectsContext = [];

        /** @var ProjectInterface $project */
        foreach ($projects as $project) {
            $projectsContext[$project->getId()] = $project;
        }

        $this->getJobContext($stepExecution)->put(self::PROJECTS_CONTEXT_KEY, $projectsContext);
        $this->getJobContext($stepExecution)->put(self::API_TEMPLATES_CONTEXT_KEY, $this->webApiRepository->getApiTemplates());
        $this->getJobContext($stepExecution)->put(self::SELECTED_ATTRIBUTES_CONTEXT_KEY, $formData->getSelectedAttributes());
        $this->getJobContext($stepExecution)->put(self::DATE_RANGE_STARTS_AT_CONTEXT_KEY, $formData->getDateRangeStartsAt());
        $this->getJobContext($stepExecution)->put(self::DATE_RANGE_ENDS_AT_CONTEXT_KEY, $formData->getDateRangeEndsAt());

        $stepExecution->incrementSummaryInfo('process');
    }

    /**
     * @param StepExecution $stepExecution
     *
     * @return array|null
     */
    protected function getConfiguredActions(StepExecution $stepExecution): ?array
    {
        $jobParameters = $stepExecution->getJobParameters();

        return $jobParameters->get('actions');
    }


    /**
     * @return string[]
     */
    protected function getDefaultAttributeCodes()
    {
        $str = $this->configManager->get('pim_textmaster.attributes');

        if (empty($str)) return [];

        return explode(',', $str);
    }

    /**
     * @param array $actions
     *
     * @return ProjectCreationFormData
     */
    protected function getProjectCreationFormData(array $actions): ProjectCreationFormData
    {
        $actionsData = reset($actions);
        $formData = new ProjectCreationFormData($actionsData);

        if ($formData->attributeOptionDefaultSelected() || $formData->attributesInDateRangeSelected()) {
            $formData->setSelectedAttributes($this->getDefaultAttributeCodes());
        }

        return $formData;
    }

    protected function validateProjectCreationFormData(ProjectCreationFormData $formData)
    {
        if (empty($formData->getSelectedAttributes())) {
            throw new InvalidArgumentException('At least 1 attributes must be sent!');
        }

        if ($formData->attributesInDateRangeSelected()) {
            $dateRangeStartsAt = $formData->getDateRangeStartsAt();
            $dateRangeEndsAt = $formData->getDateRangeEndsAt();

            if (!$dateRangeStartsAt || !$dateRangeEndsAt) {
                throw new InvalidArgumentException('Invalid date range!');
            }

            if ($dateRangeEndsAt->getTimestamp() < $dateRangeStartsAt->getTimestamp()) {
                throw new InvalidArgumentException('End of date range should be after the start date!');
            }
        }
    }

    /**
     * @param StepExecution $stepExecution
     *
     * @return ExecutionContext
     */
    protected function getJobContext(StepExecution $stepExecution): ExecutionContext
    {
        $executionContext = $stepExecution->getJobExecution()->getExecutionContext();

        if (null === $executionContext) {
            $executionContext = new ExecutionContext();
            $stepExecution->getJobExecution()->setExecutionContext($executionContext);
        }

        return $executionContext;
    }
}
