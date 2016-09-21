<?php

namespace spec\Pim\Bundle\TextmasterBundle\Locale;

use PhpSpec\ObjectBehavior;
use Pim\Bundle\TextmasterBundle\Api\WebApiRepository;
use Pim\Component\Catalog\Repository\LocaleRepositoryInterface;

/**
 * @author    Jean-Marie Leroux <jean-marie.leroux@akeneo.com>
 * @copyright 2016 TextMaster.com (https://textmaster.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class FinderSpec extends ObjectBehavior
{
    function let(
        WebApiRepository $apiRepository,
        LocaleRepositoryInterface $localeRepository)
    {
        $pimLocales = ['en_US', 'fr_FR', 'de_DE'];
        $localeRepository->getActivatedLocaleCodes()->willReturn($pimLocales);
        $apiRepository->getAvailableLocaleCodes($pimLocales)->willReturn(['en_US', 'fr_FR']);
        
        $this->beConstructedWith($apiRepository, $localeRepository);
    }

    function it_can_get_translatable_locales()
    {
        $this->getTranslatableLocalesCodes()->shouldBeArray();
    }
}
