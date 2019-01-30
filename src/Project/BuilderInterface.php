<?php

namespace Pim\Bundle\TextmasterBundle\Project;

/**
 * TextMaster builder.
 * Can build project and document payload from PIM data
 *
 * @author    Jean-Marie Leroux <jean-marie.leroux@akeneo.com>
 * @copyright 2016 TextMaster.com (https://textmaster.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
interface BuilderInterface
{
    /**
     * @param ProjectInterface $project
     *
     * @return array
     */
    public function createProjectData(ProjectInterface $project);
    
    /**
     * @param mixed  $product
     * @param string $localeCode
     *
     * @return array
     */
    public function createDocumentData($product, $localeCode);
}
