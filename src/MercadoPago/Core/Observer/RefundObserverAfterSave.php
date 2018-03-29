<?php

namespace MercadoPago\Core\Observer;

use Magento\Framework\Event\ObserverInterface;

/**
 * Class RefundObserverAfterSave
 *
 * @package MercadoPago\Core\Observer
 */
class RefundObserverAfterSave
    implements ObserverInterface
{
    /**
     * @var \MercadoPago\Core\Helper\StatusUpdate
     */
    protected $_statusHelper;

    protected $_dataHelper;
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    protected $_scopeCode;

    /**
     * RefundObserverAfterSave constructor.
     *
     * @param \MercadoPago\Core\Helper\Data $dataHelper
     */
    public function __construct(
        \MercadoPago\Core\Helper\Data                       $dataHelper,
        \Magento\Framework\App\Config\ScopeConfigInterface  $scopeConfig,
        \MercadoPago\Core\Helper\StatusUpdate               $statusHelper
    )
    {
        $this->_dataHelper = $dataHelper;
        $this->_scopeConfig = $scopeConfig;
        $this->_statusHelper = $statusHelper;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $this->_creditMemoRefundAfterSave($observer);
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    protected function _creditMemoRefundAfterSave(\Magento\Framework\Event\Observer $observer)
    {
        /**
         * @var $order      \Magento\Sales\Model\Order
         * @var $creditMemo \Magento\Sales\Model\Order\Creditmemo
         */
        $creditMemo = $observer->getData('creditmemo');
        $status = $this->_statusHelper->getOrderStatusRefunded();
        $order = $creditMemo->getOrder();
        $scopeCode = $order->getStoreId();

        $status = $this->_scopeConfig->getValue(
            \MercadoPago\Core\Helper\Data::XML_PATH_ORDER_STATUS_REFUNDED,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $scopeCode
        );

        $paymentMethod = $order->getPayment()->getMethodInstance()->getCode();
        if (!($paymentMethod == 'mercadopago_standard' || $paymentMethod == 'mercadopago_custom')) {

            return;
        }

        $message = ($order->getExternalRequest() != null ? 'From Mercado Pago' : 'From Store');
        if ($order->getMercadoPagoRefund() || $order->getExternalRequest()) {
            if ($order->getState() != $status) {
                $order->setState($status)
                    ->setStatus($order->getConfig()->getStateDefaultStatus($status))
                    ->addStatusHistoryComment('Partially Refunded ' . $message);
                $notificationData ["external_reference"] = $order->getIncrementId();
                $notificationData ["status"] = $status;
                $this->_statusHelper->setStatusUpdated($notificationData);
            }
        }
    }
}