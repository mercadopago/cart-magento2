<?php

namespace MercadoPago\Core\Helper;

/**
 * Class Country
 * @package MercadoPago\Core\Helper
 */
class Country
{
    /**
     *
     * Get country
     *
     * @param String
     * @return array
     */
    public static function getCountryToMp($country)
    {
        $mpCountries = [
            'MLA' => [ // Argentinian.
                'sufix_url' => 'com.ar',
                'translate' => 'es',
            ],
            'MLB' => [ // Brazil.
                'sufix_url' => 'com.br',
                'translate' => 'pt',
            ],
            'MLC' => [ // Chile.
                'sufix_url' => 'cl',
                'translate' => 'es',
            ],
            'MCO' => [ // Colombia.
                'sufix_url' => 'com.co',
                'translate' => 'es',
            ],
            'MLM' => [ // Mexico.
                'sufix_url' => 'com.mx',
                'translate' => 'es',
            ],
            'MPE' => [ // Peru.
                'sufix_url' => 'com.pe',
                'translate' => 'es',
            ],
            'MLU' => [ // Uruguay.
                'sufix_url' => 'com.uy',
                'translate' => 'es',
            ],
        ];

        return array_key_exists($country, $mpCountries) ? $mpCountries[$country] : $mpCountries['MLA'];
    }
}
