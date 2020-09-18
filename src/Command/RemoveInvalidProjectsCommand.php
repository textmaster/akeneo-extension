<?php

namespace Pim\Bundle\TextmasterBundle\Command;

use Pim\Bundle\TextmasterBundle\Api\WebApiRepositoryInterface;
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
     * @var WebApiRepositoryInterface
     */
    private $webApiRepository;

    /**
     * RemoveInvalidProjectsCommand constructor.
     * @param ProjectManager $projectManager
     * @param DocumentManager $documentManager
     * @param WebApiRepositoryInterface $webApiRepository
     */
    public function __construct(ProjectManager $projectManager, DocumentManager $documentManager, WebApiRepositoryInterface $webApiRepository)
    {
        parent::__construct();
        $this->projectManager = $projectManager;
        $this->documentManager = $documentManager;
        $this->webApiRepository = $webApiRepository;
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

    /**
     * Retrieve api template by id.
     *
     * @param string $apiTemplateId
     *
     * @return array|null
     */
    protected function getApiTemplateById(string $apiTemplateId): ?array
    {
        if (null === $this->apiTemplates) {
            $this->apiTemplates = $this->webApiRepository->getApiTemplates();
        }

        return isset($this->apiTemplates[$apiTemplateId]) ? $this->apiTemplates[$apiTemplateId] : null;
    }
}