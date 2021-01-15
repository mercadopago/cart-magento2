<?php

namespace MercadoPago\Core\Block\Basic;

use Magento\Framework\View\Element\Template;
use MercadoPago\Core\Model\Api\Exception as MercadoPagoException;

/**
 * Class Failure
 * @package MercadoPago\Core\Block\Basic
 */
class Failure extends Template
{
    /**
     * Failure construct
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('basic/failure.phtml');
    }

    /**
     * @return string
     */
    public function getErrorMessage()
    {
        return MercadoPagoException::GENERIC_API_EXCEPTION_MESSAGE;
    }

    /**
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getUrlHome()
    {
        return $this->_storeManager->getStore()->getBaseUrl();
    }
}
