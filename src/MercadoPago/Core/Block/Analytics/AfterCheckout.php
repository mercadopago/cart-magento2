<?php

namespace MercadoPago\Core\Block\Analytics;

class AfterCheckout extends \Magento\Framework\View\Element\Template
{

    /**
     * @var \Magento\Framework\Registry
     */
    protected $_catalogSession;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Catalog\Model\Session $catalogSession,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_catalogSession = $catalogSession;
    }

    public function getPaymentData()
    {
        return $this->_catalogSession->getPaymentData();
    }

}
