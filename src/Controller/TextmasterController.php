<?php

namespace Pim\Bundle\TextmasterBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class TextmasterController.
 *
 * @package Pim\Bundle\TextmasterBundle\Controller
 * @author  Jessy JURKOWSKI <jessy.jurkowski@cgi.com>
 */
class TextmasterController extends AbstractController
{
    /** @var string */
    private $appBaseUri;

    /**
     * TextmasterController constructor.
     *
     * @param string $appBaseUri
     */
    public function __construct(string $appBaseUri)
    {
        $this->appBaseUri = $appBaseUri;
    }

    /**
     * @param string $projectId
     * @param string $textmasterDocumentId
     *
     * @return RedirectResponse
     */
    public function openBlankToProject(string $projectId, string $textmasterDocumentId): RedirectResponse
    {
        $url = sprintf(
            '%s/clients/projects/%s/documents/%s',
            rtrim($this->appBaseUri, '/'),
            $projectId,
            $textmasterDocumentId
        );

        return $this->redirect($url);
    }
}
