<?php

namespace Pim\Bundle\TextmasterBundle\Step;

use Akeneo\Tool\Component\Batch\Item\ExecutionContext;
use Akeneo\Tool\Component\Batch\Model\StepExecution;
use Akeneo\Tool\Component\Batch\Step\AbstractStep;
use Exception;
use Pim\Bundle\TextmasterBundle\Manager\DocumentManager;
use Pim\Bundle\TextmasterBundle\Manager\ProjectManager;
use Pim\Bundle\TextmasterBundle\Model\ProjectInterface;

/**
 * Class FlagProjectToSendStep.
 *
 * @package Pim\Bundle\TextmasterBundle\Step
 * @author  Jessy JURKOWSKI <jessy.jurkowski@cgi.com>
 */
class FlagProjectsToSendStep extends AbstractStep
{
    /** @var ProjectManager */
    protected $projectManager;

    /** @var DocumentManager */
    protected $documentManager;

    /**
     * @inheritdoc
     */
    protected function doExecute(StepExecution $stepExecution)
    {
        $projectIds = array_keys($this->getProjects($stepExecution));

        try {
            $this->projectManager->flagProjectsToSendByIds($projectIds);
        } catch (Exception $exception) {
            $this->documentManager->deleteDocumentsByProjectIds($projectIds);
            $this->projectManager->deleteProjectsByIds($projectIds);
            throw $exception;
        }

        $stepExecution->incrementSummaryInfo('process');
    }

    /**
     * @param ProjectManager $projectManager
     */
    public function setProjectManager(ProjectManager $projectManager): void
    {
        $this->projectManager = $projectManager;
    }

    /**
     * @param DocumentManager $documentManager
     */
    public function setDocumentManager(DocumentManager $documentManager): void
    {
        $this->documentManager = $documentManager;
    }

    /**
     * @param StepExecution $stepExecution
     *
     * @return ProjectInterface[]
     */
    protected function getProjects(StepExecution $stepExecution): array
    {
        return (array)$this->getJobContext($stepExecution)->get(PrepareProjectsStep::PROJECTS_CONTEXT_KEY);
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