<?php

namespace Pim\Bundle\TextmasterBundle\Command;

use Exception;
use Pim\Bundle\TextmasterBundle\Manager\ProjectManager;
use Pim\Bundle\TextmasterBundle\Model\ProjectInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class UpdateProductsCommand.
 *
 * @package Pim\Bundle\TextmasterBundle\Command
 * @author  Jessy JURKOWSKI <jessy.jurkowski@cgi.com>
 */
class UpdateProductsCommand extends ContainerAwareCommand
{
    use LockableTrait;
    use CommandTrait;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('pim:textmaster:update-products')
            ->setDescription('Update products data from textmaster and update projects.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;

        if (!$this->lock()) {
            $this->writeMessage(
                sprintf('The command "%s" is still running in another process.', self::$defaultName)
            );

            return;
        }

        try {
            $this->updateProducts($input);
        } catch (Exception $exception) {
            $this->release();
            throw $exception;
        }

        $this->release();
    }

    /**
     * Update products using product update command.
     *
     * @param InputInterface $input
     */
    protected function updateProducts(InputInterface $input)
    {
        foreach ($this->getProjectsForUpdate() as $projectId) {
            $arguments = [
                UpdateProductsSubCommand::PROJECT_ID_ARGUMENT => $projectId,
            ];

            $this->runCommand($input, 'pim:textmaster:update-products-sub', $arguments);
        }
    }

    /**
     * Retrieve projects with status "finalized".
     *
     * @return array
     */
    protected function getProjectsForUpdate(): array
    {
        return $this->getProjectManager()->getProjectIdsByStatus(ProjectManager::FINALIZE_STATUS);
    }
}
