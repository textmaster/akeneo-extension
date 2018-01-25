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
class LocaleFinderSpec extends ObjectBehavior
{
    private $pimLocales = ['en_US', 'fr_FR', 'de_DE'];
    private $tmLocales = ['en-US', 'fr-FR', 'de-DE'];

    function let(
        WebApiRepository $apiRepository,
        LocaleRepositoryInterface $localeRepository)
    {
        $localeRepository->getActivatedLocaleCodes()->willReturn($this->pimLocales);
        $apiRepository->getAvailableLocaleCodes($this->pimLocales)->willReturn($this->tmLocales);
        
        $this->beConstructedWith($apiRepository, $localeRepository);
    }

    function it_can_get_translatable_locales()
    {
        $this->getTranslatableLocalesCodes()->shouldReturn($this->tmLocales);
    }

    function it_can_get_PIM_locale_from_Texmaster_locale()
    {
        $this->getPimLocaleCode('en-US')->shouldReturn('en_US');
        $this->getPimLocaleCode('fr-fr')->shouldReturn('fr_FR');
    }

    function it_can_get_Textmaster_locale_from_PIM_locale()
    {
        $this->getTextmasterLocaleCode('en_US')->shouldReturn('en-US');
    }
}
