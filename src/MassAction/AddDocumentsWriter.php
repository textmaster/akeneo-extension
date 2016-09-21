<?php

namespace Pim\Bundle\TextmasterBundle\MassAction;

use Akeneo\Component\Batch\Item\ExecutionContext;
use Akeneo\Component\Batch\Item\ItemWriterInterface;
use Akeneo\Component\Batch\Model\StepExecution;
use Akeneo\Component\Batch\Step\StepExecutionAwareInterface;
use Pim\Bundle\TextmasterBundle\Project\ProjectInterface;
use Pim\Bundle\TextmasterBundle\Api\WebApiRepository;

/**
 * Send documents to TextMaster API
 *
 * @author    Jean-Marie Leroux <jean-marie.leroux@akeneo.com>
 * @copyright 2016 TextMaster.com (https://textmaster.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class AddDocumentsWriter implements ItemWriterInterface, StepExecutionAwareInterface
{
    /** @var StepExecution */
    protected $stepExecution;

    /** @var WebApiRepository */
    protected $apiRepository;

    /**
     * @param WebApiRepository $apiRepository
     */
    public function __construct(WebApiRepository $apiRepository)
    {
        $this->apiRepository = $apiRepository;
    }

    /**
     * @param array $items
     */
    public function write(array $items)
    {
        $projects = $this->getProjects();

        $result = null;
        foreach ($projects as $project) {
            $this->apiRepository->sendProjectDocuments($items, $project->getCode());
            $this->stepExecution->incrementSummaryInfo('documents_added', count($items));
        }
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
