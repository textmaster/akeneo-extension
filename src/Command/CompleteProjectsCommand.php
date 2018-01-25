<?php

namespace Pim\Bundle\TextmasterBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Textmaster\Model\Project;

/**
 * Retrieve all TextMaster projects
 *
 * @author    Jean-Marie Leroux <jean-marie.leroux@akeneo.com>
 * @copyright 2016 TextMaster.com (https://textmaster.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class CompleteProjectsCommand extends ContainerAwareCommand
{
    /** @var OutputInterface */
    private $output;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('pim:textmaster:complete-projects')
            ->setDescription('Complete projects in_review via TextMaster API call');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $this->writeMessage('<info>List TextMaster projects</info>');

        $projects = $this->getProjects();
        foreach ($projects as $project) {
            $this->writeMessage(sprintf(
                '<info>Project %s [%s]: %s</info>',
                $project->getId(),
                $project->getStatus(),
                $project->getName()
            ));
        }
    }

    /**
     * @return Project[]
     */
    protected function getProjects()
    {
        $webApiRepository = $this->getContainer()->get('pim_textmaster.repository.webapi');
        $projects = $webApiRepository->getProjects([
            'archived' => false,
            'status' => Project::STATUS_IN_REVIEW,
        ]);

        return $projects;
    }

    /**
     * @param string $message
     */
    private function writeMessage($message)
    {
        $this->output->writeln(sprintf('%s - %s', date('Y-m-d H:i:s'), trim($message)));
    }
}
