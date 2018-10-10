<?php

namespace Pim\Bundle\TextmasterBundle\Doctrine\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Pim\Bundle\DataGridBundle\Doctrine\ORM\Repository\DatagridRepositoryInterface;
use Pim\Bundle\TextmasterBundle\Model\DocumentInterface;

/**
 * Class DocumentRepository.
 *
 * @package Pim\Bundle\Doctrine\Repository
 * @author  Jessy JURKOWSKI <jessy.jurkowski@cgi.com>
 */
class DocumentRepository extends EntityRepository implements DatagridRepositoryInterface
{
    /**
     * Retrieve document by its project and product identifiers
     *
     * @param string $projectIdentifier
     * @param string $productIdentifier
     *
     * @return null|DocumentInterface|object
     */
    public function findOneByProjectAndProductIdentifiers(string $projectIdentifier, string $productIdentifier)
    {
        return $this->findOneBy(
            [
                'projectIdentifier' => $projectIdentifier,
                'productIdentifier' => $productIdentifier,
            ]
        );
    }

    /**
     * @return QueryBuilder
     */
    public function createDatagridQueryBuilder()
    {
        $queryBuilder = $this->createQueryBuilder('d');

        return $queryBuilder->select(
            [
                'd.projectIdentifier',
                'd.productIdentifier',
                'd.productLabel',
                'd.language',
                'd.status',
                'd.updatedAt',
            ]
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

    /**
     * Remove completed documents by unretrieve projects.
     *
     * @param array $foundProjectIds
     *
     * @return mixed
     */
    public function removeCompletedDocuments(array $foundProjectIds)
    {
        $queryBuilder = $this->createQueryBuilder('d');

        $queryBuilder->delete();

        if (!empty($foundProjectIds)) {
            $queryBuilder
                ->where(
                    $queryBuilder->expr()->notIn('d.projectIdentifier', ':projectIdentifiers')
                )
                ->setParameter('projectIdentifiers', $foundProjectIds);
        }

        return $queryBuilder->getQuery()->execute();
    }
}
