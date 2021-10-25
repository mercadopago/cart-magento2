<?php

namespace MercadoPago\Core\Block\CustomPix;

class Success extends \MercadoPago\Core\Block\AbstractSuccess
{
    /**
     * Constructor
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('custom_pix/success.phtml');
    }
}
