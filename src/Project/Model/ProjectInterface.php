<?php

namespace Pim\Bundle\TextmasterBundle\Project\Model;

use Textmaster\Model\ProjectInterface as BaseProjectInterface;

/**
 * TextMaster Project.
 * Override Worldia Project Model to add features.
 * These features could be added to base library later on.
 *
 * @author    Jean-Marie Leroux <jean-marie.leroux@akeneo.com>
 * @copyright 2018 TextMaster.com (https://textmaster.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
interface ProjectInterface extends BaseProjectInterface
{
    /**
     * Get project's documents statuses
     *
     * @return int[]
     */
    public function getDocumentsStatuses();
}
