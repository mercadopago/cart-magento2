<?php

namespace MercadoPago\Core\Controller\Checkout;

use Exception;
use Magento\Catalog\Model\Session as CatalogSession;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\OrderFactory;
use Magento\Store\Model\ScopeInterface;
use MercadoPago\Core\Helper\ConfigData;
use MercadoPago\Core\Helper\Data;
use MercadoPago\Core\Model\Core;
use MercadoPago\Core\Model\Notifications\Topics\Payment;
use Psr\Log\LoggerInterface;

class Page extends Action
{
    /**
     * @var Session
     */
    protected $_checkoutSession;

    /**
     * @var OrderFactory
     */
    protected $_orderFactory;

    /**
     * @var OrderSender
     */
    protected $_orderSender;

    /**
     * @var LoggerInterface
     */
    protected $_logger;

    /**
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var Data
     */
    protected $_helperData;

    /**
     * @var Core
     */
    protected $_core;

    /**
     * @var Registry
     */
    protected $_catalogSession;

    /**
     * @var
     */
    protected $_configData;

    /**
     * @var Payment
     */
    protected $_paymentNotification;

    /**
     * Page constructor.
     * @param Context $context
     * @param Session $checkoutSession
     * @param OrderFactory $orderFactory
     * @param OrderSender $orderSender
     * @param LoggerInterface $logger
     * @param Data $helperData
     * @param ScopeConfigInterface $scopeConfig
     * @param Core $core
     * @param CatalogSession $catalogSession
     * @param Payment $paymentNotification
     */

    public function __construct(
        Context              $context,
        Session              $checkoutSession,
        OrderFactory         $orderFactory,
        OrderSender          $orderSender,
        LoggerInterface      $logger,
        Data                 $helperData,
        ScopeConfigInterface $scopeConfig,
        Core                 $core,
        CatalogSession       $catalogSession,
        Payment              $paymentNotification
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
        $this->_paymentNotification = $paymentNotification;

        parent::__construct($context);
    }

    /**
     * @return void
     */
    public function execute()
    {
        try {
            if (!$this->_scopeConfig->isSetFlag(ConfigData::PATH_ADVANCED_SUCCESS_PAGE, ScopeInterface::SCOPE_STORE)) {
                $order = $this->_getOrder();
                $payment = $order->getPayment();
                $paymentResponse = $payment->getAdditionalInformation("paymentResponse");
                $status = null;

                if (isset($paymentResponse['status'])) {
                    $status = $paymentResponse['status'];
                }

                if ($status == 'approved' || $status == 'pending') {
                    $this->approvedValidation($paymentResponse);
                    $this->_redirect('checkout/onepage/success');
                } else {
                    $this->_redirect('checkout/onepage/failure/');
                }
            } else {
                $checkoutTypeHandle = $this->getCheckoutHandle();
                $this->_view->loadLayout(['default', $checkoutTypeHandle]);
                $this->dispatchSuccessActionObserver();
                $this->_view->renderLayout();
            }
        } catch (Exception $e) {
            $this->_helperData->log('Error: ' . $e->getMessage(), 'mercadopago.log');
        }
    }

    /**
     * @return mixed
     */
    protected function _getOrder()
    {
        $orderIncrementId = $this->_checkoutSession->getLastRealOrderId();
        return $this->_orderFactory->create()->loadByIncrementId($orderIncrementId);
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

    /**
     * @param $payment
     * @throws LocalizedException
     */
    public function approvedValidation($payment)
    {
        if ($payment['status'] == 'approved') {
            if ($this->_scopeConfig->isSetFlag(ConfigData::PATH_CUSTOM_BINARY_MODE, ScopeInterface::SCOPE_STORE)) {
                $paymentResponse = $this->_core->getPaymentV1($payment['id']);

                if ($paymentResponse['status'] == 200) {
                    $this->_paymentNotification->updateStatusOrderByPayment($paymentResponse['response']);
                }
            }

            $this->dispatchSuccessActionObserver();
        }
    }

    /**
     * Dispatch checkout_onepage_controller_success_action
     */
    public function dispatchSuccessActionObserver()
    {
        $this->_eventManager->dispatch(
            'checkout_onepage_controller_success_action',
            [
                'order_ids' => [$this->_getOrder()->getId()],
                'order' => $this->_getOrder()
            ]
        );
    }
}
