<?php

use Behat\Behat\Context\SnippetAcceptingContext;
use Pim\Bundle\TextmasterBundle\Api\WebApiRepository;
use Textmaster\HttpClient\HttpClient;

class FeatureContext implements SnippetAcceptingContext
{
    /** @var WebApiRepository */
    protected $apiRepository;

    /**
     * @Given I am authenticated
     */
    public function iAmAuthenticated()
    {
        $credentials = require __DIR__ . '/api_credentials.php';
        
        $httpClient = new HttpClient(
            $credentials['api_key'],
            $credentials['api_secret'], 
            [
                'base_uri' => 'http://api.sandbox.textmaster.com/v1'
            ]
        );
        $textmasterClient = new \Textmaster\Client($httpClient);
        $this->apiRepository = new WebApiRepository($textmasterClient);
    }

    /**
     * @Then I can access my projects
     */
    public function iCanAccessMyProjects()
    {
        $response = $this->apiRepository->getProjectCodes([]);
        if (!is_array($response)) {
            throw new \RuntimeException('Response should be an array');
        }
    }
}
