<?php

namespace Pim\Bundle\TextmasterBundle\Command;

use Akeneo\Tool\Component\StorageUtils\Detacher\ObjectDetacherInterface;
use Exception;
use Pim\Bundle\TextmasterBundle\Api\WebApiRepositoryInterface;
use Pim\Bundle\TextmasterBundle\Builder\ProjectBuilderInterface;
use Pim\Bundle\TextmasterBundle\Manager\DocumentManager;
use Pim\Bundle\TextmasterBundle\Manager\ProjectManager;
use Pim\Bundle\TextmasterBundle\Model\DocumentInterface;
use Pim\Bundle\TextmasterBundle\Model\ProjectInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class CreateProjectCommand.
 *
 * @package Pim\Bundle\TextmasterBundle\Command
 * @author  Jessy JURKOWSKI <jessy.jurkowski@cgi.com>
 */
class CreateProjectCommand extends Command
{
    use LockableTrait;
    use CommandTrait;

    protected static $defaultName = 'pim:textmaster:create-project';

    protected const BATCH_SIZE = 50;

    /**
     * @var ObjectDetacherInterface
     */
    private $objectDetacher;

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
     * @var ProjectBuilderInterface
     */
    private $projectBuilder;

    public function __construct(
        ProjectManager $projectManager, 
        DocumentManager $documentManager, 
        WebApiRepositoryInterface $webApiRepository,
        ProjectBuilderInterface $projectBuilder,
        ObjectDetacherInterface $objectDetacher
    )     {
        parent::__construct();

        $this->objectDetacher = $objectDetacher;
        $this->projectManager = $projectManager;
        $this->documentManager = $documentManager;
        $this->webApiRepository = $webApiRepository;
        $this->projectBuilder = $projectBuilder;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::$defaultName)
            ->setDescription('Create project and document into textmaster');
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

        $this->writeMessage('Remove project without documents.');
        $ids = $this->projectManager->deleteUselessProjects();

        foreach ($this->getProjectsToSend() as $projectId) {
            $documentIds = $this->documentManager->getDocumentIdsByProjectId($projectId);

            if (empty($documentIds)) {
                $this->projectManager->deleteProjectsByIds([$projectId]);
                $this->writeMessage(
                    sprintf('No document to send for project with id %s', $projectId)
                );

                continue;
            }

            try {
                $project = $this->projectManager->getProjectById($projectId);
                $this->sendProject($project);
                $this->sendProjectDocuments($project, $documentIds);
                $this->saveProject($project);
            } catch (Exception $exception) {
                $this->release();
                throw $exception;
            }
        }

        $this->release();
    }

    /**
     * Send new project in Textmaster and update information into project object.
     *
     * @param ProjectInterface $project
     */
    protected function sendProject(ProjectInterface $project): void
    {
        $response = $this->webApiRepository->createProject(
            $this->projectBuilder->createProjectData($project)
        );

        $project->setTextmasterProjectId($response['id']);
        $project->setTextmasterStatus($response['status']);
    }

    /**
     * Send all project documents per batch.
     *
     * @param ProjectInterface $project
     * @param array            $documentIds
     */
    protected function sendProjectDocuments(ProjectInterface $project, array $documentIds): void
    {
        $batchDocumentIds = array_chunk($documentIds, self::BATCH_SIZE);

        foreach ($batchDocumentIds as $docs) {
            $this->sendDocuments($project, $docs);
        }
    }

    /**
     * Send provided documents to textmaster.
     *
     * @param ProjectInterface $project
     * @param array            $documentIds
     */
    protected function sendDocuments(ProjectInterface $project, array $documentIds): void
    {
        $documents = $this->documentManager->getDocumentsbyIds($documentIds);

        /** @var DocumentInterface $document */
        foreach ($documents as $document) {
            $result = $this->webApiRepository->createDocument(
                $project->getTextmasterProjectId(),
                json_decode($document->getDataToSend(), true)
            );

            $document->setTextmasterDocumentId($result['id']);
            $document->setStatus($result['status']);
        }

        $this->documentManager->saveDocuments($documents);
        $this->objectDetacher->detachAll($documents);
    }

    /**
     * Retrieve projects with status "to_send".
     *
     * @return ProjectInterface[]
     */
    protected function getProjectsToSend(): array
    {
        return $this->projectManager->getProjectIdsByStatus(ProjectManager::TO_SEND_STATUS);
    }

    /**
     * Set project status to finalize and save project.
     *
     * @param ProjectInterface $project
     */
    protected function saveProject(ProjectInterface $project): void
    {
        $project->setAkeneoStatus(ProjectManager::TO_FINALIZE_STATUS);
        $this->projectManager->saveProject($project);
    }
}
