<?php

namespace MercadoPago\Core\Block\CustomTicket;

class Success extends \MercadoPago\Core\Block\AbstractSuccess
{
    /**
     * Constructor
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('custom_ticket/success.phtml');
    }
}
