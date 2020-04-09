<?php

namespace Pim\Bundle\TextmasterBundle\Builder;

use DateTimeInterface;
use Pim\Bundle\TextmasterBundle\Model\ProjectInterface;

/**
 * TextMaster builder.
 * Can build project and document payload from PIM data
 *
 * @author    Jean-Marie Leroux <jean-marie.leroux@akeneo.com>
 * @copyright 2016 TextMaster.com (https://textmaster.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
interface ProjectBuilderInterface
{
    /**
     * @param ProjectInterface $project
     *
     * @return array
     */
    public function createProjectData(ProjectInterface $project);

    /**
     * @param mixed $product
     * @param string[] $attributeCodes
     * @param string $localeCode
     * @param DateTimeInterface $startDate
     * @param DateTimeInterface $endDate
     * @return array
     */
    public function createDocumentData($product, array $attributeCodes, $localeCode, ?DateTimeInterface $startDate = null, ?DateTimeInterface $endDate = null);
}
