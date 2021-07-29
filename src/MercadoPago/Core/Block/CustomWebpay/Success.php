<?php


namespace MercadoPago\Core\Block\CustomWebpay;


use MercadoPago\Core\Block\AbstractSuccess;

/**
 * Class Success
 * @package MercadoPago\Core\Block\CustomWebpay
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
