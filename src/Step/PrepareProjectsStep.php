<?php

namespace Pim\Bundle\TextmasterBundle\Step;

use Akeneo\Tool\Component\Batch\Item\ExecutionContext;
use Akeneo\Tool\Component\Batch\Model\StepExecution;
use Akeneo\Tool\Component\Batch\Step\AbstractStep;
use Pim\Bundle\TextmasterBundle\Api\WebApiRepositoryInterface;
use Pim\Bundle\TextmasterBundle\Manager\ProjectManager;
use Pim\Bundle\TextmasterBundle\Model\ProjectInterface;

/**
 * Class PrepareProjectsStep.
 *
 * @package Pim\Bundle\TextmasterBundle\Step
 * @author  Jessy JURKOWSKI <jessy.jurkowski@cgi.com>
 */
class PrepareProjectsStep extends AbstractStep
{
    public const PROJECTS_CONTEXT_KEY = 'textmaster_projects';
    public const API_TEMPLATES_CONTEXT_KEY = 'textmaster_api_template';

    /** @var WebApiRepositoryInterface */
    protected $webApiRepository;

    /** @var ProjectManager */
    protected $projectManager;

    /**
     * @param WebApiRepositoryInterface $webApiRepository
     */
    public function setWebApiRepository(WebApiRepositoryInterface $webApiRepository): void
    {
        $this->webApiRepository = $webApiRepository;
    }

    /**
     * @param ProjectManager $projectManager
     */
    public function setProjectManager(ProjectManager $projectManager)
    {
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

        list($projectName, $apiTemplateIds, $username) = $this->getProjectsData($actions);

        $projects = [];

        foreach ($apiTemplateIds as $apiTemplateId) {
            $projects[] = $this->projectManager->createProject(
                $username,
                $projectName,
                $apiTemplateId
            );
        }

        $this->projectManager->saveProjects($projects);
        $this->setContextKeys($stepExecution, $projects);

        $stepExecution->incrementSummaryInfo('process');
    }

    /**
     * setContextKeys
     *
     * @param StepExecution      $stepExecution
     * @param ProjectInterface[] $projects
     */
    protected function setContextKeys(StepExecution $stepExecution, array $projects)
    {
        $projectsContext = [];

        /** @var ProjectInterface $project */
        foreach ($projects as $project) {
            $projectsContext[$project->getId()] = $project;
        }

        $this->getJobContext($stepExecution)->put(self::PROJECTS_CONTEXT_KEY, $projectsContext);
        $this->getJobContext($stepExecution)->put(
            self::API_TEMPLATES_CONTEXT_KEY,
            $this->webApiRepository->getApiTemplates()
        );
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
     * @param array $actions
     *
     * @return array
     */
    protected function getProjectsData(array $actions): array
    {
        $actionsData = reset($actions);

        return [
            $actionsData['name'],
            explode(',', $actionsData['apiTemplates']),
            $actionsData['username'],
        ];
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
