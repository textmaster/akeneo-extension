<?php

namespace Pim\Bundle\TextmasterBundle\Project\Model;

use Textmaster\Model\Project as BaseProject;

/**
 * TextMaster Project.
 * Override Worldia Project Model to add features.
 * These features could be added to base library later on.
 *
 * @author    Jean-Marie Leroux <jean-marie.leroux@akeneo.com>
 * @copyright 2018 TextMaster.com (https://textmaster.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Project extends BaseProject implements ProjectInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDocumentsStatuses()
    {
        return $this->getProperty('documents_statuses');
    }
}
