<?php


namespace MercadoPago\Core\Helper;

/**
 * Class Pix
 */
class Pix
{
    /**
     * Pix expiration minutes
     *
     * @var string[]
     */
    const EXPIRATION_TIME = [
        '15 minutes' => '15',
        '30 minutes' => '30',
        '60 minutes' => '60',
        '12 hours'   => '720',
        '24 hours'   => '1440',
    ];
}//end class
