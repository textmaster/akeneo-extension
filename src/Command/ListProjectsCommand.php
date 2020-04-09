<?php

namespace Pim\Bundle\TextmasterBundle\Command;

use Pim\Bundle\TextmasterBundle\Api\WebApiRepositoryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Textmaster\Model\ProjectInterface;

/**
 * Retrieve all TextMaster projects
 *
 * @author    Jean-Marie Leroux <jean-marie.leroux@akeneo.com>
 * @copyright 2016 TextMaster.com (https://textmaster.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ListProjectsCommand extends Command
{
    /** @var OutputInterface */
    private $output;

    /**
     * @var WebApiRepositoryInterface
     */
    private $webApiRepository;

    public function __construct(WebApiRepositoryInterface $webApiRepository)
    {
        parent::__construct();
        $this->webApiRepository = $webApiRepository;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('pim:textmaster:list-projects')
            ->setDescription('Fetch projects via TextMaster API call');
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
     * @return ProjectInterface[]
     */
    protected function getProjects()
    {
        $projects = $this->webApiRepository->getProjects([
            'archived' => false,
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
