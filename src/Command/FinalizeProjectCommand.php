<?php

namespace Pim\Bundle\TextmasterBundle\Command;

use LogicException;
use Pim\Bundle\TextmasterBundle\Manager\ProjectManager;
use Pim\Bundle\TextmasterBundle\Model\ProjectInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
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
class FinalizeProjectCommand extends ContainerAwareCommand
{
    use LockableTrait;
    use CommandTrait;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('pim:textmaster:finalize-project')
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
            $project = $this->getProjectManager()->getProjectById($projectId);

            try {
                $this->writeMessage(
                    sprintf(
                        'Finalize project %s with Textmaster id %s.',
                        $project->getId(),
                        $project->getTextmasterProjectId()
                    )
                );
                $this->finalizeProject($project);
                $this->getProjectManager()->saveProject($project);
            } catch (Throwable $exception) {
                if ($exception instanceof LogicException && Response::HTTP_NOT_FOUND === $exception->getCode()) {
                    $this->getDocumentManager()->deleteDocumentsByProjectIds([$project->getId()]);
                    $this->getProjectManager()->deleteProjectsByIds([$project->getId()]);

                    $this->writeMessage(
                        sprintf(
                            'Project %s with Textmaster id %s not found in Textmaster. the project was deleted in Akeneo',
                            $project->getId(),
                            $project->getTextmasterProjectId()
                        )
                    );

                    $this->getObjectDetacher()->detach($project);
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
        $apiProject = $this->getWebApiRepository()->getProject($project->getTextmasterProjectId());

        if (true === $apiProject->isFinalized()) {
            $project->setAkeneoStatus(ProjectManager::FINALIZE_STATUS);
            $project->setTextmasterStatus($apiProject->getStatus());
        } elseif (false === $apiProject->isAutoLaunch() || true === $this->checkDocumentsCounted($project)) {
            $response = $this->getWebApiRepository()->finalizeProject($project->getTextmasterProjectId());
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
        return $this->getProjectManager()->getProjectIdsByStatus(ProjectManager::TO_FINALIZE_STATUS);
    }
}
