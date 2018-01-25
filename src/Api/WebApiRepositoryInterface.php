<?php

namespace Pim\Bundle\TextmasterBundle\Api;

use Textmaster\Model\ProjectInterface;

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
     * @param array  $documents
     * @param string $projectId
     *
     * @return array
     */
    public function sendProjectDocuments(array $documents, $projectId);

    /**
     * @param array $data
     *
     * @return array
     */
    public function createProject(array $data);

    /**
     * @param array  $data
     * @param string $projectId
     *
     * @return array
     */
    public function updateProject(array $data, $projectId);

    /**
     * @param array $filters
     *
     * @return ProjectInterface[]
     */
    public function getProjects(array $filters);

    /**
     * @param array $filters
     *
     * @return string[]
     */
    public function getProjectCodes(array $filters);

    /**
     * @param string $projectCode
     *
     * @return ProjectInterface
     */
    public function getProject($projectCode);

    /**
     * @param string $projectCode
     *
     * @return ProjectInterface
     */
    public function launchProject($projectCode);

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
    public function archiveProject($projectId);

    /**
     * @param array  $filters
     * @param string $projectCode
     *
     * @return \Textmaster\Model\DocumentInterface[]
     */
    public function getDocuments(array $filters, $projectCode);

    /**
     * @param string[] $pimLocaleCodes
     *
     * @return \string[]
     */
    public function getAvailableLocaleCodes(array $pimLocaleCodes);

    /**
     * @return array
     */
    public function getCategories();

    /**
     * @return array
     */
    public function getApiTemplates();
}
