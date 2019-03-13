<?php

namespace Pim\Bundle\TextmasterBundle\Controller;

use Pim\Bundle\TextmasterBundle\Api\WebApiRepositoryInterface;
use Pim\Bundle\TextmasterBundle\Manager\DashboardManager;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @author    Jean-Marie Leroux <jean-marie.leroux@akeneo.com>
 * @copyright 2017 TextMaster.com (https://textmaster.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class TextmasterRestController
{
    /** @var WebApiRepositoryInterface */
    private $apiRepository;

    /** @var DashboardManager */
    private $dashboardManager;

    /**
     * TextmasterRestController constructor.
     *
     * @param WebApiRepositoryInterface $apiRepository
     * @param DashboardManager          $dashboardManager
     */
    public function __construct(
        WebApiRepositoryInterface $apiRepository,
        DashboardManager $dashboardManager
    ) {
        $this->apiRepository    = $apiRepository;
        $this->dashboardManager = $dashboardManager;
    }

    /**
     * @return JsonResponse
     */
    public function fetchTextmasterStatusData(): JsonResponse
    {
        $dashboardData = $this->dashboardManager->getDocumentStatuses();

        return new JsonResponse($dashboardData);
    }

    /**
     * @return JsonResponse
     */
    public function fetchTextmasterCategories(): JsonResponse
    {
        $categories = $this->apiRepository->getCategories();

        return new JsonResponse($categories);
    }

    /**
     * @return JsonResponse
     */
    public function fetchTextmasterApiTemplates(): JsonResponse
    {
        $apiTemplates = $this->apiRepository->getApiTemplates();

        return new JsonResponse($apiTemplates);
    }
}
