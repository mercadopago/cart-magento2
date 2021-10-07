<?php

namespace MercadoPago\Core\Block\Custom;

use MercadoPago\Core\Block\AbstractSuccess;

/**
 * Class Success
 *
 * @package MercadoPago\Core\Block\Custom
 */
class Success extends AbstractSuccess
{
    /**
     * Class constructor
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('custom/success.phtml');
    }
}
