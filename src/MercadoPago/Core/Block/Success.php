<?php

namespace MercadoPago\Core\Block;

class Success extends AbstractSuccess
{
    /**
     * Constructor
     */
    protected function _construct()
    {
        parent::_construct();

        if ($this->getPaymentMethod() == 'mercadopago_basic') {
            return $this->setTemplate('basic.phtml');
        }

        if ($this->getPaymentMethod() == 'mercadopago_custom_pix') {
            return $this->setTemplate('pix.phtml');
        }

        if ($this->getPaymentMethod() == 'mercadopago_customticket') {
            return $this->setTemplate('ticket.phtml');
        }

        if ($this->getPaymentMethod() == 'mercadopago_custom') {
            return $this->setTemplate('custom.phtml');
        }
    }
}
