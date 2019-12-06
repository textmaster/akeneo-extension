<?php

namespace Pim\Bundle\TextmasterBundle\Doctrine\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\PimDataGridBundle\Doctrine\ORM\Repository\DatagridRepositoryInterface;
use Pim\Bundle\TextmasterBundle\Entity\Document;
use Pim\Bundle\TextmasterBundle\Model\DocumentInterface;

/**
 * Class DocumentRepository.
 *
 * @package Pim\Bundle\TextmasterBundle\Doctrine\Repository
 * @author  Jessy JURKOWSKI <jessy.jurkowski@cgi.com>
 */
class DocumentRepository extends EntityRepository implements DatagridRepositoryInterface
{
    /**
     * Delete document item using project identifiers.
     *
     * @param array $projectIds
     *
     * @return int
     */
    public function deleteByProjectIds(array $projectIds): int
    {
        $queryBuilder = $this->createQueryBuilder('d');

        return $queryBuilder->delete()
            ->where(
                $queryBuilder->expr()->in('d.projectId', $projectIds)
            )
            ->getQuery()
            ->execute();
    }

    /**
     * Retrieve all documents by akeneo project id.
     *
     * @param string $projectId
     *
     * @return Document[]
     */
    public function findIdsByProjectId(string $projectId): array
    {
        $queryBuilder = $this->createQueryBuilder('d');

        return $queryBuilder
            ->select('d.id')
            ->where(
                $queryBuilder->expr()->eq('d.projectId', ':id')
            )
            ->setParameter('id', $projectId)
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * Retrieve all documents by akeneo project id.
     *
     * @param string $projectId
     *
     * @return Document[]
     */
    public function findIdsAndProductIdsByProjectId(string $projectId): array
    {
        $queryBuilder = $this->createQueryBuilder('d');

        return $queryBuilder
            ->select(['d.id', 'd.productId'])
            ->where(
                $queryBuilder->expr()->eq('d.projectId', ':projectId')
            )
            ->setParameter('projectId', $projectId)
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * Retrieve documents by their ids.
     *
     * @param array $ids
     *
     * @return DocumentInterface[]
     */
    public function findByIds(array $ids): array
    {
        $queryBuilder = $this->createQueryBuilder('d');

        return $queryBuilder
            ->where(
                $queryBuilder->expr()->in('d.id', ':ids')
            )
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return QueryBuilder
     */
    public function createDatagridQueryBuilder()
    {
        $queryBuilder = $this->createQueryBuilder('d');

        return $queryBuilder
            ->select(
                [
                    'p.textmasterProjectId as projectId',
                    'd.textmasterDocumentId',
                    'd.productId AS id',
                    'd.productIdentifier',
                    'd.productLabel',
                    'd.languageFrom',
                    'd.languageTo',
                    'd.status',
                    'd.updatedAt',
                ]
            )
            ->leftJoin(
                'Pim\Bundle\TextmasterBundle\Entity\Project', 'p',
                Join::WITH,
                $queryBuilder->expr()->eq('d.projectId', 'p.id')
            );
    }

    /**
     * Retrieve all status from document table.
     *
     * @return array
     */
    public function findAllDocumentStatus(): array
    {
        $queryBuilder = $this->createQueryBuilder('d');

        return $queryBuilder->select('d.status')
            ->getQuery()
            ->getArrayResult();
    }
}
