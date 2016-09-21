<?php

namespace Pim\Bundle\TextmasterBundle\Locale;

use Pim\Bundle\TextmasterBundle\Api\WebApiRepository;
use Pim\Component\Catalog\Repository\LocaleRepositoryInterface;

/**
 * Locale finder utility
 *
 * @author    Jean-Marie Leroux <jean-marie.leroux@akeneo.com>
 * @copyright 2016 TextMaster.com (https://textmaster.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Finder
{
    /** @var WebApiRepository */
    protected $apiRepository;

    /** @var LocaleRepositoryInterface */
    protected $localeRepository;

    /**
     * @param WebApiRepository          $apiRepository
     * @param LocaleRepositoryInterface $localeRepository
     */
    public function __construct(
        WebApiRepository $apiRepository,
        LocaleRepositoryInterface $localeRepository
    ) {
        $this->apiRepository = $apiRepository;
        $this->localeRepository = $localeRepository;
    }

    /**
     * @return string[]
     */
    public function getTranslatableLocalesCodes()
    {
        $pimLocales = $this->localeRepository->getActivatedLocaleCodes();
        $textmasterLocales = $this->apiRepository->getAvailableLocaleCodes($pimLocales);

        // TODO to be continued, waiting for PR https://github.com/worldia/textmaster-api/pull/75
        return $textmasterLocales;
    }
}
