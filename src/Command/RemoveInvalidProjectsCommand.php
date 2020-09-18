<?php

namespace Pim\Bundle\TextmasterBundle\Command;

use Pim\Bundle\TextmasterBundle\Manager\DocumentManager;
use Pim\Bundle\TextmasterBundle\Manager\ProjectManager;
use Pim\Bundle\TextmasterBundle\Model\ProjectInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RemoveInvalidProjectsCommand extends ContainerAwareCommand
{
    use LockableTrait;
    use CommandTrait;

    /**
     * @var ProjectManager
     */
    private $projectManager;

    /**
     * @var DocumentManager
     */
    private $documentManager;

    /**
     * RemoveInvalidProjectsCommand constructor.
     * @param ProjectManager $projectManager
     * @param DocumentManager $documentManager
     */
    public function __construct(ProjectManager $projectManager, DocumentManager $documentManager)
    {
        parent::__construct();

        $this->projectManager = $projectManager;
        $this->documentManager = $documentManager;
    }


    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('pim:textmaster:remove-invalid-projects')
            ->setDescription('Remove invalid projects.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;

        if (false === $this->lock()) {
            $this->writeMessage(
                sprintf('The command "%s" is still running in another process.', self::$defaultName)
            );

            return;
        }
        $this->deleteInvalidProjects();
        $this->release();
    }

    /**
     * Delete projects has invalid API Template ID
     */
    public function deleteInvalidProjects()
    {
        $projects = $this->projectManager->getAllProjects();
        /**
         * @var $project ProjectInterface
         */
        foreach ($projects as $project) {
            $api = $this->getApiTemplateById($project->getApiTemplateId());
            if (null === $api) {
                $this->projectManager->deleteProjectsByIds([$project->getId()]);
                $this->documentManager->deleteDocumentsByProjectIds([$project->getId()]);
                $this->writeMessage(
                    sprintf(
                        'Project %s with Textmaster id %s has invalid API template ID: %s. the project was deleted in Akeneo',
                        $project->getId(),
                        $project->getTextmasterProjectId(),
                        $project->getApiTemplateId()
                    )
                );
            }
        }
    }
}