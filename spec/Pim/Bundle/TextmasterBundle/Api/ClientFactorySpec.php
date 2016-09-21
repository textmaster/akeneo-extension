<?php

namespace spec\Pim\Bundle\TextmasterBundle\Api;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use PhpSpec\ObjectBehavior;
use Textmaster\HttpClient\HttpClient;

/**
 * @author    Jean-Marie Leroux <jean-marie.leroux@akeneo.com>
 * @copyright 2016 TextMaster.com (https://textmaster.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ClientFactorySpec extends ObjectBehavior
{
    function let(ConfigManager $configManager)
    {
        $configManager->get('pim_textmaster.api_key')->willReturn('fookey');
        $configManager->get('pim_textmaster.api_secret')->willReturn('foosecret');
        $this->beConstructedWith($configManager);
    }

    function it_can_create_http_client()
    {
        $this->createHttpClient([])->shouldBeAnInstanceOf(HttpClient::class);
    }
}
