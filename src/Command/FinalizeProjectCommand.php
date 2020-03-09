<?php

namespace Pim\Bundle\TextmasterBundle\Command;

use Akeneo\Tool\Component\StorageUtils\Detacher\ObjectDetacherInterface;
use LogicException;
use Pim\Bundle\TextmasterBundle\Api\WebApiRepositoryInterface;
use Pim\Bundle\TextmasterBundle\Manager\DocumentManager;
use Pim\Bundle\TextmasterBundle\Manager\ProjectManager;
use Pim\Bundle\TextmasterBundle\Model\ProjectInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\Response;
use Textmaster\Model\DocumentInterface as ApiDocumentInterface;
use Throwable;

/**
 * Class FinalizeProjectCommand.
 *
 * @package Pim\Bundle\TextmasterBundle\Command
 * @author  Jessy JURKOWSKI <jessy.jurkowski@cgi.com>
 */
class FinalizeProjectCommand extends Command
{
    use LockableTrait;
    use CommandTrait;

    protected static $defaultName = 'pim:textmaster:finalize-project';

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
     * @var ObjectDetacherInterface
     */
    private $objectDetacher;

    public function __construct(
        ProjectManager $projectManager,
        DocumentManager $documentManager,
        WebApiRepositoryInterface $webApiRepository,
        ObjectDetacherInterface $objectDetacher
    ) {
        parent::__construct();
        $this->projectManager = $projectManager;
        $this->documentManager = $documentManager;
        $this->webApiRepository = $webApiRepository;
        $this->objectDetacher = $objectDetacher;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::$defaultName)
            ->setDescription('Finalize project into textmaster.');
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

        foreach ($this->getProjectsToFinalize() as $projectId) {
            /** @var ProjectInterface $project */
            $project = $this->projectManager->getProjectById($projectId);

            try {
                $this->writeMessage(
                    sprintf(
                        'Finalize project %s with Textmaster id %s.',
                        $project->getId(),
                        $project->getTextmasterProjectId()
                    )
                );
                $this->finalizeProject($project);
                $this->projectManager->saveProject($project);
            } catch (Throwable $exception) {
                if ($exception instanceof LogicException && Response::HTTP_NOT_FOUND === $exception->getCode()) {
                    $this->documentManager->deleteDocumentsByProjectIds([$project->getId()]);
                    $this->projectManager->deleteProjectsByIds([$project->getId()]);

                    $this->writeMessage(
                        sprintf(
                            'Project %s with Textmaster id %s not found in Textmaster. the project was deleted in Akeneo',
                            $project->getId(),
                            $project->getTextmasterProjectId()
                        )
                    );

                    $this->objectDetacher->detach($project);
                    continue;
                }

                $this->release();
                throw $exception;
            }
        }

        $this->release();
    }

    /**
     * Finalize project if it's possible.
     *
     * @param ProjectInterface $project
     */
    protected function finalizeProject(ProjectInterface $project): void
    {
        $apiProject = $this->webApiRepository->getProject($project->getTextmasterProjectId());

        if (true === $apiProject->isFinalized()) {
            $project->setAkeneoStatus(ProjectManager::FINALIZE_STATUS);
            $project->setTextmasterStatus($apiProject->getStatus());
        } elseif (false === $apiProject->isAutoLaunch() || true === $this->checkDocumentsCounted($project)) {
            $response = $this->webApiRepository->finalizeProject($project->getTextmasterProjectId());
            $project->setAkeneoStatus(ProjectManager::FINALIZE_STATUS);
            $project->setTextmasterStatus($response['status']);
        } else {
            $project->setAkeneoStatus(ProjectManager::TO_FINALIZE_STATUS);
        }
    }

    /**
     * Check if all documents are counted.
     *
     * @param ProjectInterface $project
     *
     * @return bool
     */
    protected function checkDocumentsCounted(ProjectInterface $project): bool
    {
        $filters = [
            'status' => [
                '$in' => [ApiDocumentInterface::STATUS_IN_CREATION],
            ],
        ];

        $apiDocuments = $this->getApiDocumentsByProject($project, $filters);

        $allCounted = true;

        /** @var ApiDocumentInterface $document */
        foreach ($apiDocuments as $document) {
            if (null === $document->getWordCount()) {
                $allCounted = false;
            }

            unset($document);
        }

        return $allCounted;
    }

    /**
     * Retrieve projects with status "to_finalize".
     *
     * @return ProjectInterface[]
     */
    protected function getProjectsToFinalize(): array
    {
        return $this->projectManager->getProjectIdsByStatus(ProjectManager::TO_FINALIZE_STATUS);
    }


    /**
     * Retrieve api document related to project.
     *
     * @param ProjectInterface $project
     * @param array            $filters
     *
     * @return ApiDocumentInterface[]
     */
    protected function getApiDocumentsByProject(ProjectInterface $project, array $filters): array
    {
        return $this
            ->webApiRepository
            ->getAllDocuments(
                $filters,
                $project->getTextmasterProjectId()
            );
    }
}
