<?php

namespace Pim\Bundle\TextmasterBundle\Controller;

use Pim\Bundle\TextmasterBundle\Api\WebApiRepositoryInterface;
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

    public function __construct(WebApiRepositoryInterface $apiRepository)
    {
        $this->apiRepository = $apiRepository;
    }

    public function fetchTextmasterCategories(): JsonResponse
    {
        $categories = $this->apiRepository->getCategories();

        return new JsonResponse($categories);
    }
}
