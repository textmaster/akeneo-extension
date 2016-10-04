<?php

namespace Pim\Bundle\TextmasterBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Textmaster\Exception\ErrorException;
use Textmaster\Model\ProjectInterface;

/**
 * Utility command: clean the TextMaster dashboard
 * - delete all in creation projects
 *
 * @author    Jean-Marie Leroux <jean-marie.leroux@akeneo.com>
 * @copyright 2016 TextMaster.com (https://textmaster.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class CleanDashboardCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('pim:textmaster:delete-all')
            ->setDescription('Delete all "in creation" projects');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $filters = [
            'status' => [
                '$in' => [ProjectInterface::STATUS_IN_CREATION],
            ],
            'archived' => false,
        ];

        $webApiRepository = $this->getContainer()->get('pim_textmaster.repository.webapi');
        $textmasterProjects = $webApiRepository->getProjectCodes($filters);

        $output->writeln(sprintf('<comment>Process %d in_creation projects</comment>', count($textmasterProjects)));

        foreach ($textmasterProjects as $projectCode) {
            try {
                $output->writeln(sprintf('<comment>Process project %s</comment>', $projectCode));
                $webApiRepository->cancelProject($projectCode);
                $output->writeln(sprintf('<comment>Cancelled project %s</comment>', $projectCode));
                $webApiRepository->archiveProject($projectCode);
                $output->writeln(sprintf('<comment>Archived project %s</comment>', $projectCode));
            } catch (ErrorException $e) {
                $output->writeln(sprintf('<error>%s</error>: %s', $projectCode, $e->getMessage()));
            }
        }
    }
}
