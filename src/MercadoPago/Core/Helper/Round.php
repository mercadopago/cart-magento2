<?php

namespace MercadoPago\Core\Helper;

use MercadoPago\Core\Helper\ConfigData;

class Round
{
    /**
     * Countries that need to have the total rounded to not have decimal values
     *
     * @var string[]
     */
    const COUNTRIES_WITH_INTEGER_PRICE = [
        'MLC',
        'MLO',
    ];

    /**
     * Get rounded value with site id
     *
     * @param  float|double $value
     * @param  string $siteId
     * @return float|integer
     */
    public static function roundWithSiteId($value, $siteId)
    {
        $round = (float) number_format($value, 2, '.', '');

        if (in_array($siteId, self::COUNTRIES_WITH_INTEGER_PRICE, true)) {
            return (int) $round;
        }

        return $round;
    }

    /**
     * Get rounded value with site id
     *
     * @param  float|double $value
     * @param  string $siteId
     * @return float|integer
     */
    public static function roundWithoutSiteId($value)
    {
        $round = (float) number_format($value, 2, '.', '');
        return $round;
    }
}
