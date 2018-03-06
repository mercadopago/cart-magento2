<?php

namespace MercadoPago\Core\Controller\Checkout;

use MercadoPago\Core\Model\Core;


/**
 * Class Success
 *
 * @package MercadoPago\Core\Controller\Success
 */
class Page
    extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $_orderFactory;

    /**
     * @var \Magento\Sales\Model\Order\Email\Sender\OrderSender
     */
    protected $_orderSender;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var \MercadoPago\Core\Helper\Data
     */
    protected $_helperData;

    protected $_core;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $_catalogSession;

    /**
     * Page constructor.
     *
     * @param Core                                                $core
     * @param \Magento\Framework\App\Action\Context               $context
     * @param \Magento\Checkout\Model\Session                     $checkoutSession
     * @param \Magento\Sales\Model\OrderFactory                   $orderFactory
     * @param \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender
     * @param \Psr\Log\LoggerInterface                            $logger
     * @param \MercadoPago\Core\Helper\Data                       $helperData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface  $scopeConfig
     * @param \MercadoPago\Core\Model\Core                        $core
     */

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender,
        \Psr\Log\LoggerInterface $logger,
        \MercadoPago\Core\Helper\Data $helperData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \MercadoPago\Core\Model\Core $core,
        \Magento\Catalog\Model\Session $catalogSession
    )
    {

        $this->_checkoutSession = $checkoutSession;
        $this->_orderFactory = $orderFactory;
        $this->_orderSender = $orderSender;
        $this->_logger = $logger;
        $this->_helperData = $helperData;
        $this->_scopeConfig = $scopeConfig;
        $this->_core = $core;
        $this->_catalogSession = $catalogSession;

        parent::__construct(
            $context
        );

    }

    /**
     * @return \Magento\Sales\Model\Order
     */
    protected function _getOrder()
    {
        $orderIncrementId = $this->_checkoutSession->getLastRealOrderId();
        $order = $this->_orderFactory->create()->loadByIncrementId($orderIncrementId);

        return $order;
    }

    /**
     * Controller action
     */
    public function execute()
    {
        if (!$this->_scopeConfig->getValue(\MercadoPago\Core\Helper\Data::XML_PATH_USE_SUCCESSPAGE_MP, \Magento\Store\Model\ScopeInterface::SCOPE_STORE)){

            $order = $this->_getOrder();
            $infoPayment = $this->_core->getInfoPaymentByOrder($order->getIncrementId());

            $status = null;

            //checkout Custom Credit Card
            if (!empty($infoPayment['status']['value'])) {
                $status = $infoPayment['status']['value'];
                //$detail = $infoPayment['status_detail']['value'];
            }
            //checkout redirect
            if ($status == 'approved' || $status == 'pending'){
                $this->_redirect('checkout/onepage/success');
            } else {
                $this->_redirect('checkout/onepage/failure/');
            }

        } else {
            //set data for mp analytics
            $this->_catalogSession->setPaymentData($this->_helperData->getAnalyticsData($this->_getOrder()));

            $checkoutTypeHandle = $this->getCheckoutHandle();
            $this->_view->loadLayout(['default', $checkoutTypeHandle]);
            $this->_eventManager->dispatch(
                'checkout_onepage_controller_success_action',
                ['order_ids' => [$this->_getOrder()->getId()]]
            );
            $this->_view->renderLayout();
        }

    }

    /**
     * Return handle name, depending on payment method used in the order placed
     *
     * @return string
     */
    public function getCheckoutHandle()
    {
        $handle = '';
        $order = $this->_getOrder();
        if (!empty($order->getId())) {
            $handle = $order->getPayment()->getMethod();
        }
        $handle .= '_success';

        return $handle;
    }
}