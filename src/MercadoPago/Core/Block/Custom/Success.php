<?php
namespace MercadoPago\Core\Block\Custom;

/**
 * Class Success
 *
 * @package MercadoPago\Core\Block\Custom
 */
class Success
    extends \MercadoPago\Core\Block\AbstractSuccess
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