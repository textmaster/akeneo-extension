<?php

namespace Pim\Bundle\TextmasterBundle\Api;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Textmaster\HttpClient\HttpClient;

/**
 * Factory to build the HttpClient with stored configuration
 *
 * @author    Jean-Marie Leroux <jean-marie.leroux@akeneo.com>
 * @copyright 2016 TextMaster.com (https://textmaster.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ClientFactory
{
    /** @var ConfigManager */
    protected $configManager;

    /**
     * @param ConfigManager $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * @param array $options
     *
     * @return HttpClient
     */
    public function createHttpClient(array $options)
    {
        $apiKey = $this->configManager->get('pim_textmaster.api_key');
        $apiSecret = $this->configManager->get('pim_textmaster.api_secret');
        $client = new HttpClient($apiKey, $apiSecret, $options);

        return $client;
    }
}
