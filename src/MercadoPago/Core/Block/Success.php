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
            return $this->setTemplate('basic/success.phtml');
        }

        if ($this->getPaymentMethod() == 'mercadopago_custom_pix') {
            return $this->setTemplate('custom_pix/success.phtml');
        }

        if ($this->getPaymentMethod() == 'mercadopago_customticket') {
            return $this->setTemplate('custom_ticket/success.phtml');
        }

        if ($this->getPaymentMethod() == 'mercadopago_custom') {
            return $this->setTemplate('custom/success.phtml');
        }

        if ($this->getPaymentMethod() == 'mercadopago_custom_bank_transfer') {
            $this->setTemplate('custom_bank_transfer/success.phtml');
        }

    }
}
