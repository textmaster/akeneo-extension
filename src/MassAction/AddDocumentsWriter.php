<?php

namespace Pim\Bundle\TextmasterBundle\MassAction;

use Akeneo\Component\Batch\Item\ItemWriterInterface;
use Akeneo\Component\Batch\Model\StepExecution;
use Akeneo\Component\Batch\Step\StepExecutionAwareInterface;
use Pim\Bundle\TextmasterBundle\Project\ProjectInterface;
use Pim\Bundle\TextmasterBundle\Api\WebApiRepository;

/**
 * Send documents to TextMaster API. It uses the projects created in the previous step.
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
     * @param array $items Projets processed before. Items is an array of projects arrays.
     */
    public function write(array $items)
    {
        foreach ($items as $projects) {
            $this->writeProject($projects);
        }
    }

    /**
     * Add documents to existing projects.
     *
     * @param ProjectInterface[] projects.
     */
    public function writeProject(array $projects)
    {
        foreach ($projects as $project) {
            $documents = $project->getDocuments();
            if (null !== $documents) {
//                $this->apiRepository->sendProjectDocuments($documents, $project->getCode());
            }
        }
    }

    /**
     * @param StepExecution $stepExecution
     */
    public function setStepExecution(StepExecution $stepExecution)
    {
        $this->stepExecution = $stepExecution;
    }
}
