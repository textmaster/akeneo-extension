<?php

namespace Pim\Bundle\TextmasterBundle\Locale;

/**
 * Locale finder utility
 *
 * @author    Jean-Marie Leroux <jean-marie.leroux@akeneo.com>
 * @copyright 2016 TextMaster.com (https://textmaster.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
interface LocaleFinderInterface
{
    /**
     * Get all Textmaster translatable locales
     *
     * @return string[]
     */
    public function getTranslatableLocalesCodes();

    /**
     * Get Textmaster locale code from PIM locale code
     *
     * @param $pimLocaleCode
     *
     * @return string
     */
    public function getTextmasterLocaleCode($pimLocaleCode);

    /**
     * Get PIM locale code from Textaster locale code
     *
     * @param $textmasterLocaleCode
     *
     * @return string
     */
    public function getPimLocaleCode($textmasterLocaleCode);
}
