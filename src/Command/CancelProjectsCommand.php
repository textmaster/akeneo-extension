<?php

namespace Pim\Bundle\TextmasterBundle\Command;

use Pim\Bundle\TextmasterBundle\Project\ProjectInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Textmaster\Exception\ErrorException;
use Textmaster\Model\ProjectInterface as ApiProjectInterface;

/**
 * A utility command to clean all remaining PIM textmaster projects
 * - cancel non completed PIM projects
 * - archive all TextMaster cancelled or completed projects
 *
 * @author    Jean-Marie Leroux <jean-marie.leroux@akeneo.com>
 * @copyright 2016 TextMaster.com (https://textmaster.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class CancelProjectsCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('pim:textmaster:cancel-projects')
            ->setDescription('Cancel all remaining projects');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $projects = $this->getProjects();

        $webApiRepository = $this->getContainer()->get('pim_textmaster.repository.webapi');

        $filters = [
            'status' => [
                '$in' => [ApiProjectInterface::STATUS_IN_CREATION],
            ],
            'archived' => false,
        ];

        $toRemove = $webApiRepository->getProjectCodes($filters);

        $output->writeln(sprintf('<comment>Process %d PIM projects</comment>', count($projects)));

        foreach ($projects as $pimProject) {
            try {
                if (in_array($pimProject->getCode(), $toRemove)) {
                    $webApiRepository->cancelProject($pimProject->getCode());
                    $output->writeln(sprintf('<comment>Cancelled project %s</comment>', $pimProject->getCode()));
                }
                $remover = $this->getContainer()->get('pim_textmaster.remover.project');
                $remover->remove($pimProject);
                $output->writeln(sprintf('<info>Removed project %s</info>', $pimProject->getCode()));
            } catch (ErrorException $e) {
                $output->writeln(sprintf('<error>%s</error>: %s', $pimProject->getCode(), $e->getMessage()));
            }
        }

        $this->archive($output);
    }

    /**
     * Archive all cancelled projects
     *
     * @param OutputInterface $output
     */
    protected function archive(OutputInterface $output)
    {
        $filters = [
            'status' => [
                '$in' => [ApiProjectInterface::STATUS_CANCELED, ApiProjectInterface::STATUS_COMPLETED],
            ],
            'archived' => false,
        ];

        $webApiRepository = $this->getContainer()->get('pim_textmaster.repository.webapi');
        $textmasterProjects = $webApiRepository->getProjectCodes($filters);

        $output->writeln(sprintf('<comment>Archive %d TextMaster projects</comment>', count($textmasterProjects)));

        foreach ($textmasterProjects as $projectCode) {
            try {
                $webApiRepository->archiveProject($projectCode);
                $output->writeln(sprintf('<comment>Archived project %s</comment>', $projectCode));
            } catch (ErrorException $e) {
                $output->writeln(sprintf('<error>%s</error>: %s', $projectCode, $e->getMessage()));
            }
        }
    }

    /**
     * @return ProjectInterface[]
     */
    protected function getProjects()
    {
        $projectRepository = $this->getContainer()->get('pim_textmaster.repository.project');
        $projects = $projectRepository->findAll();

        return $projects;
    }
}
