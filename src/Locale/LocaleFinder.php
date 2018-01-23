<?php

namespace Pim\Bundle\TextmasterBundle\Locale;

use Pim\Bundle\TextmasterBundle\Api\WebApiRepository;
use Pim\Component\Catalog\Repository\LocaleRepositoryInterface;

/**
 * Locale finder utility. Everything to find locales and code from one system to the other.
 *
 * @author    Jean-Marie Leroux <jean-marie.leroux@akeneo.com>
 * @copyright 2016 TextMaster.com (https://textmaster.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class LocaleFinder implements LocaleFinderInterface
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
     * {@inheritdoc}
     */
    public function getTranslatableLocalesCodes()
    {
        $pimLocales = $this->localeRepository->getActivatedLocaleCodes();
        $textmasterLocales = $this->apiRepository->getAvailableLocaleCodes($pimLocales);

        return $textmasterLocales;
    }

    /**
     * {@inheritdoc}
     */
    public function getTextmasterLocaleCode($pimLocaleCode)
    {
        list($left, $right) = explode('_', $pimLocaleCode);

        return sprintf('%s-%s', strtolower($left), strtoupper($right));
    }

    /**
     * {@inheritdoc}
     */
    public function getPimLocaleCode($textmasterLocaleCode)
    {
        list($left, $right) = explode('-', $textmasterLocaleCode);

        return sprintf('%s_%s', strtolower($left), strtoupper($right));
    }
}
