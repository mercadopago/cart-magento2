<?php

namespace MercadoPago\Core\Logger;

/**
 * MercadoPago custom logger allows name changing to differentiate log call origin
 * Class Logger
 *
 * @package MercadoPago\Core\Logger
 */
class Logger extends \Monolog\Logger
{

    /**
     * Set logger name
     * @param $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }
}
