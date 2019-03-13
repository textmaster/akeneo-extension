<?php

namespace Pim\Bundle\TextmasterBundle\Project;

use Doctrine\ORM\EntityRepository;

/**
 * PIM Project repository
 *
 * @author    Jean-Marie Leroux <jean-marie.leroux@akeneo.com>
 * @copyright 2016 TextMaster.com (https://textmaster.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ProjectRepository extends EntityRepository
{
    /**
     * Remove projects by their code.
     *
     * @param array $projectCodes
     */
    public function removeProjectsByCode(array $projectCodes): void
    {
        $queryBuilder = $this->createQueryBuilder('p');

        $queryBuilder->delete()
            ->where($queryBuilder->expr()->in('p.code', ':codes'))
            ->setParameter('codes', $projectCodes)
            ->getQuery()
            ->execute();
    }
}
