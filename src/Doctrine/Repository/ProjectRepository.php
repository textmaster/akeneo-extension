<?php

namespace Pim\Bundle\TextmasterBundle\Doctrine\Repository;

use DateTime;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Pim\Bundle\TextmasterBundle\Manager\ProjectManager;

/**
 * Class ProjectRepository.
 *
 * @package Pim\Bundle\TextmasterBundle\Doctrine\Repository
 * @author  Jessy JURKOWSKI <jessy.jurkowski@cgi.com>
 */
class ProjectRepository extends EntityRepository
{
    /**
     * Delete project items using project identifiers.
     *
     * @param array $projectIds
     *
     * @return int
     */
    public function deleteByIds(array $projectIds): int
    {
        $queryBuilder = $this->createQueryBuilder('p');

        return $queryBuilder->delete()
            ->where(
                $queryBuilder->expr()->in('p.id', $projectIds)
            )
            ->getQuery()
            ->execute();
    }

    /**
     * Update project item status to "to_send" using akeneo project identifiers.
     *
     * @param array $projectIds
     *
     * @return int
     */
    public function flagToSendByIds(array $projectIds): int
    {
        $queryBuilder = $this->createQueryBuilder('p');

        return $queryBuilder->update()
            ->set('p.akeneoStatus', ':status')
            ->setParameter('status', ProjectManager::TO_SEND_STATUS)
            ->set('p.updatedAt', ':date')
            ->setParameter('date', new DateTime())
            ->where(
                $queryBuilder->expr()->in('p.id', $projectIds)
            )
            ->getQuery()
            ->execute();
    }

    /**
     * Retrieve project item ids by their status.
     *
     * @param string $status
     *
     * @return array
     */
    public function findIdsByStatus(string $status)
    {
        $queryBuilder = $this->createQueryBuilder('p');

        return $queryBuilder
            ->select('p.id')
            ->where(
                $queryBuilder->expr()->eq('p.akeneoStatus', ':status')
            )
            ->setParameter('status', $status)
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * Retrieve empty project ids.
     *
     * @return array
     */
    public function getEmptyProjectIds(): array
    {
        $queryBuilder = $this->createQueryBuilder('p');

        return $queryBuilder
            ->select('p.id')
            ->leftJoin(
                'Pim\Bundle\TextmasterBundle\Entity\Document',
                'd',
                Join::WITH,
                $queryBuilder->expr()->eq('p.id', 'd.projectId')
            )
            ->where(
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->in('p.akeneoStatus', ':akeneoStatus'),
                    $queryBuilder->expr()->isNull('d.id')
                )
            )
            ->setParameter(
                'akeneoStatus',
                [
                    ProjectManager::TO_SEND_STATUS,
                    ProjectManager::SENT_STATUS,
                    ProjectManager::TO_FINALIZE_STATUS,
                    ProjectManager::FINALIZE_STATUS
                ]
            )
            ->getQuery()
            ->getArrayResult();
    }
}
