<?php

namespace Pim\Bundle\TextmasterBundle\Provider;

/**
 * Class LocaleProvider.
 *
 * @package Pim\Bundle\TextmasterBundle\Provider
 * @author  Jessy JURKOWSKI <jessy.jurkowski@cgi.com>
 */
class LocaleProvider
{
    /**
     * Get PIM locale code from Textaster locale code
     *
     * @param $textmasterLocaleCode
     *
     * @return string
     */
    public function getPimLocaleCode($textmasterLocaleCode): string
    {
        list($left, $right) = explode('-', $textmasterLocaleCode);

        return sprintf('%s_%s', strtolower($left), strtoupper($right));
    }
}