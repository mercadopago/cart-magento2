<?php

namespace MercadoPago\Core\Block\CustomWebpay;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;

/**
 * Class Failure
 *
 * @package MercadoPago\Core\Block\CustomWebpay
 */
class Failure extends Template
{
    /**
     * Failure construct.
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('custom_webpay/failure.phtml');
    }

    /**
     * @return string
     * @throws NoSuchEntityException
     */
    public function getHomeUrl()
    {
        return $this->_storeManager->getStore()->getBaseUrl();
    }
}

