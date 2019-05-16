<?php

namespace Pim\Bundle\TextmasterBundle\MassAction;

use Akeneo\Tool\Component\Batch\Item\ExecutionContext;
use Akeneo\Tool\Component\Batch\Model\StepExecution;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Pim\Bundle\TextmasterBundle\Api\WebApiRepository;
use Pim\Bundle\TextmasterBundle\Project\ProjectInterface;
use Akeneo\Tool\Component\Connector\Step\TaskletInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Send previously built projects
 *
 * @author    Jean-Marie Leroux <jean-marie.leroux@akeneo.com>
 * @copyright 2018 TextMaster.com (https://textmaster.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class SendProjectsTasklet implements TaskletInterface
{
    const STATUS_MAX_TRY = 3;

    /** @var StepExecution */
    protected $stepExecution;

    /** @var WebApiRepository */
    protected $apiRepository;

    /** @var ConfigManager */
    protected $configManager;

    /** @var TranslatorInterface */
    protected $translator;

    /**
     * @param WebApiRepository    $apiRepository
     * @param ConfigManager       $configManager
     * @param TranslatorInterface $translator
     */
    public function __construct(
        WebApiRepository $apiRepository,
        ConfigManager $configManager,
        TranslatorInterface $translator
    ) {
        $this->apiRepository = $apiRepository;
        $this->configManager = $configManager;
        $this->translator = $translator;
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
        $projects = $this->getProjects();

        foreach ($projects as $project) {
            $documents = $project->getDocuments();
            if (null !== $documents) {
                $this->apiRepository->sendProjectDocuments($documents, $project->getCode());
                $this->stepExecution->incrementSummaryInfo('projects_sent', 1);
                $this->stepExecution->incrementSummaryInfo('documents_added', count($documents));
            }
        }
    }

    /**
     * Wait for a requested status
     *
     * @param ProjectInterface $project
     * @param string           $status
     *
     * @return bool
     */
    protected function waitForStatus(ProjectInterface $project, $status)
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
     * @param ProjectInterface $project
     *
     * @return array
     */
    protected function startMemoryTranslation(ProjectInterface $project)
    {
        $data = [
            'project' => [
                'options' => [
                    'language_level'     => 'enterprise',
                    'translation_memory' => true,
                ],
            ],
        ];

        return $this->apiRepository->updateProject($data, $project->getCode());
    }

    /**
     * @return ProjectInterface[]
     */
    protected function getProjects()
    {
        return (array)$this->getJobContext()->get(CreateProjectsTasklet::PROJECTS_CONTEXT_KEY);
    }

    /**
     * @return ExecutionContext
     */
    protected function getJobContext()
    {
        return $this->stepExecution->getJobExecution()->getExecutionContext();
    }
}
