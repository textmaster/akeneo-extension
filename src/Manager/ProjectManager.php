<?php

namespace Pim\Bundle\TextmasterBundle\Manager;

use Akeneo\Tool\Component\StorageUtils\Factory\SimpleFactoryInterface;
use Akeneo\Tool\Component\StorageUtils\Saver\BulkSaverInterface;
use Akeneo\Tool\Component\StorageUtils\Saver\SaverInterface;
use DateTime;
use Doctrine\Common\Persistence\ObjectRepository;
use Pim\Bundle\TextmasterBundle\Doctrine\Repository\ProjectRepository;
use Pim\Bundle\TextmasterBundle\Model\ProjectInterface;

/**
 * Class ProjectManager.
 *
 * @package Pim\Bundle\TextmasterBundle\Manager
 * @author  Jessy JURKOWSKI <jessy.jurkowski@cgi.com>
 */
class ProjectManager
{
    public const INIT_STATUS = 'init';
    public const TO_SEND_STATUS = 'to_send';
    public const SENT_STATUS = 'sent';
    public const TO_FINALIZE_STATUS = 'to_finalize';
    public const FINALIZE_STATUS = 'finalized';

    /** @var SimpleFactoryInterface */
    protected $factory;

    /** @var ObjectRepository|ProjectRepository */
    protected $repository;

    /** @var SaverInterface|BulkSaverInterface */
    protected $saver;

    /**
     * ProjectManager constructor.
     *
     * @param SimpleFactoryInterface                  $factory
     * @param ObjectRepository|ProjectRepository $repository
     * @param BulkSaverInterface                      $saver
     */
    public function __construct(
        SimpleFactoryInterface $factory,
        ObjectRepository $repository,
        BulkSaverInterface $saver
    ) {
        $this->factory    = $factory;
        $this->repository = $repository;
        $this->saver      = $saver;
    }

    /**
     * @param string $username
     * @param string $name
     * @param string $apiTemplateId
     *
     * @return ProjectInterface
     */
    public function createProject(
        string $username,
        string $name,
        string $apiTemplateId
    ): ProjectInterface {
        /** @var ProjectInterface $project */
        $project = $this->factory->create();

        return $project->setUsername($username)
            ->setName($name)
            ->setApiTemplateId($apiTemplateId)
            ->setAkeneoStatus(self::INIT_STATUS)
            ->setCreatedAt(new DateTime())
            ->setUpdatedAt(new DateTime());
    }

    /**
     * Save project provided.
     *
     * @param ProjectInterface $project
     */
    public function saveProject(ProjectInterface $project): void
    {
        $project->setUpdatedAt(new DateTime());
        $this->saver->save($project);
    }

    /**
     * Save project items provided.
     *
     * @param array ProjectInterface[]
     */
    public function saveProjects(array $projects): void
    {
        foreach ($projects as $project) {
            $project->setUpdatedAt(new DateTime());
        }

        $this->saver->saveAll($projects);
    }

    /**
     * Retrieve project items using akeneo project identifiers.
     *
     * @param string $projectId
     *
     * @return ProjectInterface|object|null
     */
    public function getProjectById(string $projectId): ?ProjectInterface
    {
        return $this->repository->findOneBy(['id' => $projectId]);
    }

    /**
     * Delete project items using project identifiers.
     *
     * @param array $projectIds
     *
     * @return int
     */
    public function deleteProjectsByIds(array $projectIds): int
    {
        return $this->repository->deleteByIds($projectIds);
    }

    /**
     * Update project item status to "to_send" using akeneo project identifiers.
     *
     * @param array $projectIds
     *
     * @return int
     */
    public function flagProjectsToSendByIds(array $projectIds): int
    {
        return $this->repository->flagToSendByIds($projectIds);
    }

    /**
     * Retrieve all project identifiers with provided status.
     *
     * @param string $status
     *
     * @return array
     */
    public function getProjectIdsByStatus(string $status): array
    {
        $projectIds = [];

        foreach ($this->repository->findIdsByStatus($status) as $projectData) {
            $projectIds[] = $projectData['id'];
        }

        return $projectIds;
    }

    /**
     * Delete all project with status "to_send" and without document related to.
     *
     * @return int
     */
    public function deleteUselessProjects(): int
    {
        $emptyProjectIds = [];

        foreach ($this->repository->getEmptyProjectIds() as $projectId) {
            $emptyProjectIds[] = reset($projectId);
        }

        return empty($emptyProjectIds) ? 0 : $this->repository->deleteByIds($emptyProjectIds);
    }

    /**
     * Get all projects
     *
     * @return array|object[]
     */
    public function getAllProjects()
    {
        return $this->repository->findAll();
    }
}
