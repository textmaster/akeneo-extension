<?php

namespace Pim\Bundle\TextmasterBundle\Controller;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
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

    /** @var ConfigManager */
    private $configManager;

    /**
     * TextmasterRestController constructor.
     *
     * @param WebApiRepositoryInterface $apiRepository
     * @param DashboardManager $dashboardManager
     * @param ConfigManager $configManager
     */
    public function __construct(
        WebApiRepositoryInterface $apiRepository,
        DashboardManager $dashboardManager,
        ConfigManager $configManager
    ) {
        $this->apiRepository    = $apiRepository;
        $this->dashboardManager = $dashboardManager;
        $this->configManager = $configManager;
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
    public function fetchTextmasterApiTemplates(): JsonResponse
    {
        $apiTemplates = $this->apiRepository->getApiTemplates();

        return new JsonResponse($apiTemplates);
    }

    public function fetchTextmasterDefaultAttributes(): JsonResponse
    {
        $attributes = explode(',', $this->configManager->get('pim_textmaster.attributes'));

        return new JsonResponse($attributes);
    }
}
