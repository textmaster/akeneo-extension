<?php

namespace Pim\Bundle\TextmasterBundle\Api;

use Pim\Bundle\TextmasterBundle\Project\Model\Project;
use Pim\Bundle\TextmasterBundle\Project\Model\ProjectInterface;
use Textmaster\Client;
use Textmaster\Exception\RuntimeException;
use Textmaster\Model\Document;

/**
 * Calls to TextMaster php API
 *
 * @author    Jean-Marie Leroux <jean-marie.leroux@akeneo.com>
 * @copyright 2016 Akeneo SAS (https://textmaster.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class WebApiRepository implements WebApiRepositoryInterface
{
    /** @var Client */
    protected $clientApi;

    /**
     * @param Client $clientApi
     */
    public function __construct(Client $clientApi)
    {
        $this->clientApi = $clientApi;
    }

    /**
     * @param array  $documents
     * @param string $projectId
     *
     * @return array
     */
    public function sendProjectDocuments(array $documents, $projectId)
    {
        $projectApi = $this->clientApi->project();

        return $projectApi->documents($projectId)->batchCreate($documents);
    }

    /**
     * @param array $data
     *
     * @return array
     */
    public function createProject(array $data)
    {
        return $this->clientApi->projects()->create($data);
    }

    /**
     * @param array  $data
     * @param string $projectId
     *
     * @return array
     */
    public function updateProject(array $data, $projectId)
    {
        return $this->clientApi->projects()->update($projectId, $data);
    }

    /**
     * @param array $filters
     *
     * @return ProjectInterface[]
     */
    public function getProjects(array $filters)
    {
        $projectApi = $this->clientApi->projects();
        $response = $projectApi->filter($filters);

        $projects = [];
        foreach ($response['projects'] as $projectData) {
            $projects[] = new Project($this->clientApi, $projectData);
        }

        return $projects;
    }

    /**
     * Retrieve all project from textmaster.
     *
     * @param array $filters
     * @param array $projects
     * @param null  $currentPage
     *
     * @return array|ProjectInterface[]
     */
    public function getAllProjects(array $filters, array &$projects = [], $currentPage = null): array
    {
        $projectApi = $this->clientApi->projects();

        if (null !== $currentPage) {
            $projectApi->setPage($currentPage);
        }

        $response = $projectApi->filter($filters);

        foreach ($response['projects'] as $projectData) {
            $projects[] = new Project($this->clientApi, $projectData);
        }

        if (null !== $response['total_pages'] && $currentPage < $response['total_pages']) {
            $this->getAllProjects($filters, $projects, (int) $response['page'] + 1);
        }

        return $projects;
    }

    /**
     * Retrieve all project from textmaster.
     *
     * @param array  $filters
     * @param string $projectCode
     * @param array  $documents
     * @param null   $currentPage
     *
     * @return array
     */
    public function getAllDocuments(
        array $filters, string $projectCode, array &$documents = [], $currentPage = null
    ): array {
        $documentApi = $this->clientApi->projects()->documents($projectCode);

        if (null !== $currentPage) {
            $documentApi->setPage($currentPage);
        }

        $response = $documentApi->filter($filters);

        foreach ($response['documents'] as $documentData) {
            $documents[] = new Document($this->clientApi, $documentData);
        }

        if (null !== $response['total_pages'] && 1 === $response['total_pages']) {
            return $documents;
        }

        if (null !== $response['total_pages'] && $currentPage < $response['total_pages']) {
            $this->getAllDocuments($filters, $projectCode, $documents, (int) $response['page'] + 1);
        }

        return $documents;
    }

    /**
     * @param array $filters
     *
     * @return string[]
     */
    public function getProjectCodes(array $filters)
    {
        $projects = $this->getProjects($filters);
        $projectsCodes = [];
        foreach ($projects as $project) {
            $projectsCodes[] = $project->getId();
        }

        return $projectsCodes;
    }

    /**
     * Get codes of all paginated project (all pages)
     *
     * @param array $filters
     *
     * @return string[]
     */
    public function getAllProjectCodes(array $filters)
    {
        $projects = $this->getAllProjects($filters);
        $projectCodes = [];

        foreach ($projects as $project) {
            $projectCodes[] = $project->getId();
        }

        return $projectCodes;
    }

    /**
     * @param string $projectCode
     *
     * @return ProjectInterface
     */
    public function getProject($projectCode)
    {
        $projectApi = $this->clientApi->project();
        $response = $projectApi->show($projectCode);

        return new Project($this->clientApi, $response);
    }

    /**
     * @param string $projectCode
     *
     * @return ProjectInterface
     */
    public function launchProject($projectCode)
    {
        $projectApi = $this->clientApi->project();
        $response = $projectApi->launch($projectCode);

        return new Project($this->clientApi, $response);
    }

    /**
     * @param string $projectId
     *
     * @return array
     */
    public function cancelProject($projectId)
    {
        $projectApi = $this->clientApi->project();

        return $projectApi->cancel($projectId);
    }

    /**
     * @param string $projectId
     *
     * @return array
     */
    public function archiveProject($projectId)
    {
        $projectApi = $this->clientApi->project();

        return $projectApi->archive($projectId);
    }

    /**
     * @param string $projectId
     *
     * @return array
     */
    public function finalizeProject($projectId)
    {
        $projectApi = $this->clientApi->project();

        return $projectApi->finalize($projectId);
    }

    /**
     * @param array  $filters
     * @param string $projectCode
     *
     * @return \Textmaster\Model\DocumentInterface[]
     */
    public function getDocuments(array $filters, $projectCode)
    {
        $documentsApi = $this->clientApi->project()->documents($projectCode);
        $documents = $documentsApi->filter($filters);
        $models = [];
        foreach ($documents['documents'] as $documentData) {
            $models[] = new Document($this->clientApi, $documentData);
        }

        return $models;
    }

    /**
     * @param string[] $pimLocaleCodes
     *
     * @return \string[]
     */
    public function getAvailableLocaleCodes(array $pimLocaleCodes)
    {
        $pimLocaleCodes = array_map(function ($localeCode) {
            return strtolower(str_replace('_', '-', $localeCode));
        }, $pimLocaleCodes);

        $availableLocales = [];
        try {
            $tmLocales = $this->clientApi->locales()->all();
            foreach ($tmLocales as $tmLocale) {
                $tmLocaleCode = strtolower($tmLocale['code']);
                if (in_array($tmLocaleCode, $pimLocaleCodes)) {
                    $availableLocales[] = $tmLocale['code'];
                }
            }
        } catch (RuntimeException $e) {
            $tmLocales = null;
        }

        return $availableLocales;
    }

    /**
     * @return array
     */
    public function getCategories()
    {
        $response = $this->clientApi->categories()->all();

        $categories = [];
        foreach ($response['categories'] as $category) {
            $categories[$category['code']] = $category['value'];
        }

        asort($categories);

        return $categories;
    }

    /**
     * @return array
     */
    public function getApiTemplates()
    {
        $response = $this->clientApi->apiTemplate()->all();

        $apiTemplates = [];
        foreach ($response['api_templates'] as $apiTemplate) {
            $apiTemplates[$apiTemplate['id']] = [
                'id'            => $apiTemplate['id'],
                'name'          => $apiTemplate['name'],
                'language_from' => $apiTemplate['language_from'],
                'language_to'   => $apiTemplate['language_to'],
            ];
        }

        return $apiTemplates;
    }
}
