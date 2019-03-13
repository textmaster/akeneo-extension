<?php

namespace Pim\Bundle\TextmasterBundle\Step;

use Akeneo\Component\Batch\Item\InvalidItemException;
use Akeneo\Component\Batch\Model\StepExecution;
use Akeneo\Component\Batch\Model\Warning;
use Pim\Component\Connector\Step\TaskletStep as ParentStep;

/**
 * Class TaskletStep.
 *
 * @package Pim\Bundle\TextmasterBundle\Step
 * @author  Jessy JURKOWSKI <jessy.jurkowski@cgi.com>
 */
class FinalizeProjectsTaskletStep extends ParentStep
{
    /**
     * doExecute
     *
     * @param StepExecution $stepExecution
     *
     * @throws \Exception
     */
    protected function doExecute(StepExecution $stepExecution)
    {
        try {
            parent::doExecute($stepExecution);
        } catch (InvalidItemException $e) {
            $this->handleStepExecutionWarning($stepExecution, $this->tasklet, $e);
        }
    }

    /**
     * Handle step execution warning
     *
     * @param StepExecution        $stepExecution
     * @param mixed                $element
     * @param InvalidItemException $e
     */
    protected function handleStepExecutionWarning(
        StepExecution $stepExecution,
        $element,
        InvalidItemException $e
    ): void {
        foreach ($stepExecution->getErrors() as $error) {
            $warning = new Warning(
                $stepExecution,
                $error,
                $e->getMessageParameters(),
                $e->getItem()->getInvalidData()
            );

            $this->jobRepository->addWarning($warning);

            $this->dispatchInvalidItemEvent(
                get_class($element),
                $error,
                $e->getMessageParameters(),
                $e->getItem()
            );
        }
    }
}