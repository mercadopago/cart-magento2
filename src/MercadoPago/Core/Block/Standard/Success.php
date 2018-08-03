<?php
namespace MercadoPago\Core\Block\Standard;

/**
 * Block to checkout success page
 *
 * Class Success
 *
 * @package MercadoPago\Core\Block\Standard
 */
class Success
    extends \MercadoPago\Core\Block\AbstractSuccess
{
    /**
     * Set template in constructor method
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('standard/success.phtml');
    }

}