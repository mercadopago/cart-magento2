<?php

namespace MercadoPago\Core\Helper;

use Magento\Framework\App\ObjectManager;
use MercadoPago\Core\Helper\ConfigData;

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
    public static function getCountryToMp($country) {
		$mpCountries = array(
			'MLA' => array( // Argentinian.
				'sufix_url' => 'com.ar',
				'translate' => 'es',
			),
			'MLB' => array( // Brazil.
				'sufix_url' => 'com.br',
				'translate' => 'pt',
			),
			'MLC' => array( // Chile.
				'sufix_url' => 'cl',
				'translate' => 'es',
			),
			'MCO' => array( // Colombia.
				'sufix_url' => 'com.co',
				'translate' => 'es',
			),
			'MLM' => array( // Mexico.
				'sufix_url' => 'com.mx',
				'translate' => 'es',
			),
			'MPE' => array( // Peru.
				'sufix_url' => 'com.pe',
				'translate' => 'es',
			),
			'MLU' => array( // Uruguay.
				'sufix_url' => 'com.uy',
				'translate' => 'es',
			),
        );

        return array_key_exists( $country, $mpCountries ) ? $mpCountries[$country] : $mpCountries['MLA'];
    }

    /**
     *
     * Change URL by country suffix
     *
     * @param String
     * @return String
     */
    public static function changeUrlByCountry($url) {

        $objectManager = ObjectManager::getInstance();
        $siteId = strtoupper(
            $objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')->getValue(ConfigData::PATH_SITE_ID),
        );

        $country = self::getCountryToMp($siteId);

        return str_replace("sufix", $country['sufix_url'], $url);
    }
}