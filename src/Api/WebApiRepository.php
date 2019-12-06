<?php

namespace Pim\Bundle\TextmasterBundle\Api;

use Pim\Bundle\TextmasterBundle\Project\Model\Document as ApiDocument;
use Pim\Bundle\TextmasterBundle\Project\Model\Project as ApiProject;
use Textmaster\Client;

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
     * {@inheritdoc}
     */
    public function createDocument(string $textmasterProjectId, array $data)
    {
        $projectApi = $this->clientApi->project();

        return $projectApi->documents($textmasterProjectId)->create($data);
    }

    /**
     * {@inheritdoc}
     */
    public function createProject(array $data)
    {
        return $this->clientApi->projects()->create($data);
    }

    /**
     * {@inheritdoc}
     */
    public function getProjects(array $filters)
    {
        $projectApi = $this->clientApi->projects();
        $response   = $projectApi->filter($filters);

        $projects = [];
        foreach ($response['projects'] as $projectData) {
            $projects[] = new ApiProject($this->clientApi, $projectData);
        }

        return $projects;
    }

    /**
     * {@inheritdoc}
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
            $documents[] = new ApiDocument($this->clientApi, $documentData);
        }

        if (null !== $response['total_pages'] && 1 === $response['total_pages']) {
            return $documents;
        }

        if (null !== $response['total_pages'] && $currentPage < $response['total_pages']) {
            $this->getAllDocuments($filters, $projectCode, $documents, (int)$response['page'] + 1);
        }

        return $documents;
    }

    /**
     * {@inheritdoc}
     */
    public function getProject($projectCode)
    {
        $projectApi = $this->clientApi->project();
        $response   = $projectApi->show($projectCode);

        return new ApiProject($this->clientApi, $response);
    }

    /**
     * {@inheritdoc}
     */
    public function cancelProject($projectId)
    {
        $projectApi = $this->clientApi->project();

        return $projectApi->cancel($projectId);
    }

    /**
     * {@inheritdoc}
     */
    public function finalizeProject($projectId)
    {
        $projectApi = $this->clientApi->project();

        return $projectApi->finalize($projectId);
    }

    /**
     * {@inheritdoc}
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
                'auto_launch'   => $apiTemplate['auto_launch'],
            ];
        }

        return $apiTemplates;
    }

    /**
     * {@inheritdoc}
     */
    public function getLangages()
    {
        return $this->clientApi->language()->all();
    }
}
