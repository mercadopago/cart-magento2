<?php


namespace MercadoPago\Core\Helper;

/**
 * Class Pix
 */
class Pix
{

    /**
     * @var string[]
     */
     const EXPIRATION_TIME = [
         '5 minutes'  => '5',
         '10 minutes' => '10',
         '15 minutes' => '15',
         '30 minutes' => '30',
         '45 minutes' => '45',
         '1 hour'     => '60',
         '2 hours'    => '120',
         '3 hours'    => '180',
         '4 hours'    => '240',
         '5 hours'    => '300',
         '6 hours'    => '360',
         '1 day'      => '1440',
         '2 days'     => '2880',
         '3 days'     => '4320',
         '4 days'     => '5760',
         '5 days'     => '7200',
         '6 days'     => '8640',
         '7 days'     => '10080',
     ];

}//end class
