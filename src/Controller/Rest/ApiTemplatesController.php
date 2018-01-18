<?php

namespace Pim\Bundle\TextmasterBundle\Controller\Rest;

use Pim\Bundle\TextmasterBundle\Api\WebApiRepository;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Rest controller for API templates
 *
 * @author    Jean-Marie Leroux <jean-marie.leroux@akeneo.com>
 * @copyright 2018 TextMaster.com (https://textmaster.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ApiTemplatesController
{
    /** @var WebApiRepository */
    private $apiRepository;

    public function __construct(WebApiRepository $apiRepository)
    {
        $this->apiRepository = $apiRepository;
    }

    public function fetchApiTemplatesAction()
    {
        $apiTermplates = $this->apiRepository->getApiTemplates();
        return new JsonResponse([
            'apiTemplates' => $apiTermplates,
        ]);
    }
}
