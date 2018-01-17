<?php

namespace Pim\Bundle\TextmasterBundle\MassAction;

use Akeneo\Component\Batch\Item\ExecutionContext;
use Akeneo\Component\Batch\Model\StepExecution;
use Akeneo\Component\StorageUtils\Saver\SaverInterface;
use Pim\Bundle\TextmasterBundle\Api\WebApiRepository;
use Pim\Bundle\TextmasterBundle\Entity\Project;
use Pim\Bundle\TextmasterBundle\Project\BuilderInterface;
use Pim\Bundle\TextmasterBundle\Project\ProjectInterface;
use Pim\Component\Catalog\Repository\LocaleRepositoryInterface;
use Pim\Component\Connector\Step\TaskletInterface;

/**
 * Create the project entity and put it in job context
 *
 * @author    Jean-Marie Leroux <jean-marie.leroux@akeneo.com>
 * @copyright 2016 TextMaster.com (https://textmaster.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class CreateProjectsTasklet implements TaskletInterface
{
    const PROJECTS_CONTEXT_KEY = 'textmaster_projects';

    /**
     * @since      1.2.1
     * @deprecated since 1.3
     * @var string Default category
     */
    const PROJECTS_DEFAULT_CATEGORY = 'C033';

    /** @var StepExecution */
    protected $stepExecution;

    /** @var LocaleRepositoryInterface */
    protected $localeRepository;

    /** @var SaverInterface */
    protected $saver;

    /** @var BuilderInterface */
    protected $builder;

    /** @var WebApiRepository */
    protected $apiRepository;

    /**
     * @param LocaleRepositoryInterface $localeRepository
     * @param BuilderInterface          $builder
     * @param WebApiRepository          $apiRepository
     * @param SaverInterface            $saver
     */
    public function __construct(
        LocaleRepositoryInterface $localeRepository,
        BuilderInterface $builder,
        WebApiRepository $apiRepository,
        SaverInterface $saver
    ) {
        $this->localeRepository = $localeRepository;
        $this->saver = $saver;
        $this->builder = $builder;
        $this->apiRepository = $apiRepository;
    }

    /**
     * @inheritdoc
     */
    public function getConfigurationFields()
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
     */
    public function execute()
    {
        $actions = $this->getConfiguredActions();

        $projectCode = $actions['name'];
        $apiTemplateIds = $actions['apiTemplates'];
        $username = $actions['username'];

        $projects = [];
        foreach ($apiTemplateIds as $apiTemplateId) {
            $project = $this->createProject(
                $projectCode,
                $apiTemplateId,
                $username
            );
            $this->sendProject($project);
            $this->saver->save($project);
            $projects[] = $project;
            $this->stepExecution->incrementSummaryInfo('process');
        }
        $this->addProjectsToContext($projects);
    }

    /**
     * @param string      $name
     * @param string      $apiTemplateId
     * @param string|null $username
     *
     * @return ProjectInterface
     */
    protected function createProject(
        $name,
        $apiTemplateId,
        $username = null
    ) {
        $project = new Project();
        $project->setName($name);
        $project->setApiTemplateId($apiTemplateId);
        $project->setUsername($username);

        return $project;
    }

    /**
     * @param ProjectInterface $project
     *
     * @return array
     */
    protected function sendProject(ProjectInterface $project)
    {
        $data = $this->builder->createProjectData($project);
        $result = $this->apiRepository->createProject($data);
        $project->setCode((string)$result['id']);

        return $result;
    }

    /**
     * @param ProjectInterface[] $projects
     */
    protected function addProjectsToContext(array $projects)
    {
        $this->getJobContext()->put(static::PROJECTS_CONTEXT_KEY, $projects);
    }

    /**
     * @return ExecutionContext
     */
    protected function getJobContext()
    {
        $jobExecution = $this->stepExecution->getJobExecution();
        $context = $jobExecution->getExecutionContext();
        if (null === $context) {
            $context = new ExecutionContext();
            $jobExecution->setExecutionContext($context);
        }

        return $context;
    }

    /**
     * @return array|null
     */
    protected function getConfiguredActions()
    {
        $jobParameters = $this->stepExecution->getJobParameters();

        return $jobParameters->get('actions');
    }
}
