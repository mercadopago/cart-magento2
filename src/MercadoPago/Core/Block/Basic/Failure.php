<?php

namespace MercadoPago\Core\Block\Basic;

use Exception;
use Magento\Checkout\Model\Session;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Quote\Model\QuoteFactory;

/**
 * Class Failure
 * @package MercadoPago\Core\Block\Basic
 */
class Failure extends Template
{
    /**
     * @var Session
     */
    protected $_checkoutSession;

    /**
     * @var QuoteFactory
     */
    protected $_quoteFactory;

    /**
     * Failure construct
     *
     * @param Context $context
     * @param Session $checkoutSession
     * @param QuoteFactory $quoteFactory
     */
    public function __construct(Context $context, Session $checkoutSession, QuoteFactory $quoteFactory)
    {
        parent::__construct($context);
        $this->_quoteFactory = $quoteFactory;
        $this->_checkoutSession = $checkoutSession;
        $this->setTemplate('basic/failure.phtml');
    }

    /**
     * @throws Exception
     */
    public function persistCartSession() {
        $order = $this->_checkoutSession->getLastRealOrder();
        $quote = $this->_quoteFactory->create()->loadByIdWithoutStore($order->getQuoteId());

        if ($quote->getId()) {
            $quote->setIsActive(true)->setReservedOrderId(null)->save();
            $this->_checkoutSession->replaceQuote($quote);
        }
    }

    /**
     * @return string
     * @throws Exception
     */
    public function getCheckoutUrl()
    {
        $this->persistCartSession();
        return $this->getUrl('checkout', ['_secure' => true]);
    }
}
