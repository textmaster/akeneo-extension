<?php

namespace spec\Pim\Bundle\TextmasterBundle\Controller;

use PhpSpec\ObjectBehavior;
use Pim\Bundle\TextmasterBundle\Api\WebApiRepositoryInterface;

/**
 * @author    Jean-Marie Leroux <jean-marie.leroux@akeneo.com>
 * @copyright 2017 TextMaster.com (https://textmaster.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ClientFactorySpec extends ObjectBehavior
{
    function let(WebApiRepositoryInterface $apiRepository)
    {
        $this->beConstructedWith($apiRepository);
    }
}
