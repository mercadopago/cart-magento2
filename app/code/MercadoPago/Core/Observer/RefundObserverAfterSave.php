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
     * @var \MercadoPago\Core\Helper\Data
     */
    protected $_dataHelper;

    /**
     * RefundObserverAfterSave constructor.
     *
     * @param \MercadoPago\Core\Helper\Data $dataHelper
     */
    public function __construct(\MercadoPago\Core\Helper\Data $dataHelper)
    {
        $this->_dataHelper = $dataHelper;
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
        $status = $this->_dataHelper->getOrderStatusRefunded();
        $order = $creditMemo->getOrder();
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
                $this->_dataHelper->setStatusUpdated($notificationData);
            }
        }
    }
}