<?php

namespace MercadoPago\Core\Helper;

class SponsorId
{
    /**
     * @param $siteId
     * @return int|null
     */
    public static function getSponsorId($siteId)
    {
        $sponsorIds = [
            'MCO' => '222570694',
            'MLA' => '222568987',
            'MLB' => '222567845',
            'MLC' => '222570571',
            'MLM' => '222568246',
            'MLU' => '247030424',
            'MPE' => '222568315',
            'MLV' => '222569730',
        ];

        if (!isset($sponsorIds[$siteId])) {
            return null;
        }

        return (int) $sponsorIds[$siteId];
    }
}
