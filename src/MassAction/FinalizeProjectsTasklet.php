<?php

namespace Pim\Bundle\TextmasterBundle\MassAction;

use Akeneo\Component\Batch\Item\AbstractConfigurableStepElement;
use Akeneo\Component\Batch\Item\ExecutionContext;
use Akeneo\Component\Batch\Model\StepExecution;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Pim\Bundle\TextmasterBundle\Project\ProjectInterface;
use Pim\Bundle\TextmasterBundle\Api\WebApiRepository;
use Pim\Component\Connector\Step\TaskletInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Finalize the mass actino:
 * - translation memory
 * - autolaunch
 *
 * @author    Jean-Marie Leroux <jean-marie.leroux@akeneo.com>
 * @copyright 2016 TextMaster.com (https://textmaster.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class FinalizeProjectsTasklet extends AbstractConfigurableStepElement implements TaskletInterface
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
    )
    {
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
    public function execute(array $configuration)
    {
        $autolaunch = $this->configManager->get('pim_textmaster.autolaunch');
        $projects = $this->getProjects();

        foreach ($projects as $project) {
            $this->waitForStatus($project, \Textmaster\Model\ProjectInterface::STATUS_IN_CREATION);
            $this->startMemoryTranslation($project);
            if ($autolaunch) {
                $this->launchProject($project);
            }
        }

        if (!$autolaunch) {
            $label = $this->translator->trans('textmaster.customer.validation_link');
            $link = sprintf('<a href="https://www.textmaster.com/clients/projects" target="_blank">%s</a>', $label);
            $this->stepExecution->addSummaryInfo('link', $link);
        } else {
            $label = $this->translator->trans('textmaster.customer.launched_link');
            $link = sprintf('<a href="https://www.textmaster.com/clients/projects" target="_blank">%s</a>', $label);
            $this->stepExecution->addSummaryInfo('link', $link);
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
                    // TODO enable translation memory when ready on TextMaster side
                    'translation_memory' => false,
                ],
            ],
        ];

        return $this->apiRepository->updateProject($data, $project->getCode());
    }

    /**
     * @param ProjectInterface $project
     *
     * @return \Textmaster\Model\ProjectInterface
     */
    protected function launchProject(ProjectInterface $project)
    {
        try {
            $this->apiRepository->launchProject($project->getCode());
            $this->stepExecution->incrementSummaryInfo('launched');
        } catch (\LogicException $e) {
            $this->stepExecution->incrementSummaryInfo('not_launched');
        }
    }

    /**
     * @return ProjectInterface[]
     */
    protected function getProjects()
    {
        return $this->getJobContext()->get(CreateProjectsTasklet::PROJECTS_CONTEXT_KEY);
    }

    /**
     * @return ExecutionContext
     */
    protected function getJobContext()
    {
        return $this->stepExecution->getJobExecution()->getExecutionContext();
    }
}
