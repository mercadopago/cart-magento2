<?php

namespace MercadoPago\Core\Block\Basic;

use MercadoPago\Core\Block\AbstractSuccess;

/**
 * Class Success
 * @package MercadoPago\Core\Block\Basic
 */
class Success extends AbstractSuccess
{
    /**
     * Success constructor
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('basic/success.phtml');
    }
}
