<?php

namespace Pim\Bundle\TextmasterBundle\Doctrine\Repository;

use Akeneo\Tool\Bundle\VersioningBundle\Doctrine\ORM\VersionRepository as BaseVersionRepository;
use Akeneo\Tool\Component\Versioning\Model\VersionInterface;
use DateTimeInterface;
use Doctrine\DBAL\ParameterType;

class VersionRepository extends BaseVersionRepository
{
    /**
     * @param DateTimeInterface $startDate
     * @param DateTimeInterface $endDate
     * @param $resource
     * @param bool $isPending
     *
     * @return VersionInterface[]
     */
    public function findLogEntriesInDateRange(
        DateTimeInterface $startDate,
        DateTimeInterface $endDate,
        $resource,
        bool $isPending = false
    ) {
        return $this->createQueryBuilder('version')
            ->andWhere('version.resourceName = :resourceName')
            ->setParameter('resourceName', get_class($resource))
            ->andWhere('version.resourceId = :resourceId')
            ->setParameter('resourceId', $resource->getId())
            ->andWhere('version.pending = :isPending')
            ->setParameter('isPending', $isPending)
            ->andWhere('version.loggedAt >= :startDate')
            ->setParameter('startDate', $startDate->format('Y-m-d'), ParameterType::STRING)
            ->andWhere('version.loggedAt <= :endDate')
            ->setParameter('endDate', $endDate->format('Y-m-d') . ' 23:59:59', ParameterType::STRING)
            ->getQuery()
            ->execute();
    }
}
