<?php

namespace MercadoPago\Core\Helper;

class Round
{
    /**
     * Countries that need to have the total rounded to not have decimal values
     *
     * @var string[]
     */
    const COUNTRIES_WITH_INTEGER_PRICE = [
        'MLC',
        'MCO',
    ];


    /**
     * Get rounded value with site id
     *
     * @param  float  $value
     * @param  string $siteId
     * @return float|integer
     */
    public static function roundWithSiteId($value, $siteId)
    {
        if (in_array($siteId, self::COUNTRIES_WITH_INTEGER_PRICE, true)) {
            return (int) number_format($value, 0, '.', '');
        }

        return (float) number_format($value, 2, '.', '');

    }//end roundWithSiteId()


    /**
     * Get rounded value with site id
     *
     * @param  float $value
     * @return float
     */
    public static function roundWithoutSiteId($value)
    {
        return (float) number_format($value, 2, '.', '');

    }//end roundWithoutSiteId()


    /**
     * Get rounded value with site id
     *
     * @param  float $value
     * @return integer
     */
    public static function roundInteger($value)
    {
        return (int) number_format($value, 0, '.', '');

    }//end roundInteger()


}//end class
