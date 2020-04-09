<?php

namespace Pim\Bundle\TextmasterBundle\Api;

use Textmaster\Model\ProjectInterface as ApiProjectInterface;

/**
 * Calls to TextMaster php API
 *
 * @author    Jean-Marie Leroux <jean-marie.leroux@akeneo.com>
 * @copyright 2016 Akeneo SAS (https://textmaster.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
interface WebApiRepositoryInterface
{
    /**
     * @param string $textmasterProjectId
     * @param array  $data
     *
     * @return array
     */
    public function createDocument(string $textmasterProjectId, array $data);

    /**
     * @param array $data
     *
     * @return array
     */
    public function createProject(array $data);

    /**
     * @param array $filters
     *
     * @return ApiProjectInterface[]
     */
    public function getProjects(array $filters);

    /**
     * @param string $projectCode
     *
     * @return ApiProjectInterface
     */
    public function getProject($projectCode);

    /**
     * @param string $projectId
     *
     * @return array
     */
    public function cancelProject($projectId);

    /**
     * @param string $projectId
     *
     * @return array
     */
    public function finalizeProject($projectId);

    /**
     * @return array
     */
    public function getApiTemplates();

    /**
     * @return array
     */
    public function getLangages();
}
