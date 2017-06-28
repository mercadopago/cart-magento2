<?php
namespace MercadoPago\Core\Helper;

/**
 * Class StatusUpdate
 *
 * @package MercadoPago\Core\Helper
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class StatusUpdate
    extends \Magento\Payment\Helper\Data
{

    protected $_finalStatus = ['rejected', 'cancelled', 'refunded', 'charge_back'];
    protected $_notFinalStatus = ['authorized', 'process', 'in_mediation'];

    /**
     * @var bool flag indicates when status was updated by notifications.
     */
    protected $_statusUpdatedFlag = false;
    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $_orderFactory;
    /**
     * @var \MercadoPago\Core\Helper\Message\MessageInterface
     */
    protected $_messageInterface;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Status\Collection
     */
    protected $_statusFactory;

    /**
     * @var \Magento\Sales\Model\Order\CreditmemoFactory
     */
    protected $_creditmemoFactory;

    /**
     * @var \Magento\Framework\DB\TransactionFactory
     */
    protected $_transactionFactory;

    /**
     * @var \Magento\Sales\Model\Order\Email\Sender\InvoiceSender
     */
    protected $_invoiceSender;

    /**
     * @var \Magento\Sales\Model\Order\Email\Sender\OrderSender
     */
    protected $_orderSender;


    protected $_dataHelper;
    protected $_coreHelper;

    public function __construct(
        \MercadoPago\Core\Helper\Message\MessageInterface $messageInterface,
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\View\LayoutFactory $layoutFactory,
        \Magento\Payment\Model\Method\Factory $paymentMethodFactory,
        \Magento\Store\Model\App\Emulation $appEmulation,
        \Magento\Payment\Model\Config $paymentConfig,
        \Magento\Framework\App\Config\Initial $initialConfig,
        \Magento\Sales\Model\ResourceModel\Status\Collection $statusFactory,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Sales\Model\Order\CreditmemoFactory $creditmemoFactory,
        \MercadoPago\Core\Helper\Data $dataHelper,
        \MercadoPago\Core\Model\Core $coreHelper,
        \Magento\Framework\DB\TransactionFactory $transactionFactory,
        \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender
    )
    {
        parent::__construct($context, $layoutFactory, $paymentMethodFactory, $appEmulation, $paymentConfig, $initialConfig);
        $this->_messageInterface = $messageInterface;
        $this->_orderFactory = $orderFactory;
        $this->_statusFactory = $statusFactory;
        $this->_creditmemoFactory = $creditmemoFactory;
        $this->_dataHelper = $dataHelper;
        $this->_coreHelper = $coreHelper;
        $this->_transactionFactory = $transactionFactory;
        $this->_invoiceSender = $invoiceSender;
        $this->_orderSender = $orderSender;
    }

    /**
     * @return bool return updated flag
     */
    public function isStatusUpdated()
    {
        return $this->_statusUpdatedFlag;
    }

    /**
     * @return mixed
     */
    public function getOrderStatusRefunded()
    {
        return $this->scopeConfig->getValue('payment/mercadopago/order_status_refunded');
    }

    /**
     * Set flag status updated
     *
     * @param $notificationData
     */
    public function setStatusUpdated($notificationData, $order)
    {
        $status = $notificationData['status'];
        $statusDetail = $notificationData['status_detail'];
        $currentStatus = $order->getPayment()->getAdditionalInformation('status');
        $currentStatusDetail = $order->getPayment()->getAdditionalInformation('status_detail');

        if (!is_null($order->getPayment()) && $order->getPayment()->getAdditionalInformation('second_card_token')) {
            $this->_statusUpdatedFlag = false;

            return;
        }
        if ($status == $currentStatus && $statusDetail == $currentStatusDetail) {
            $this->_statusUpdatedFlag = true;
        }
    }

    protected function _getMulticardLastValue($value)
    {
        $statuses = explode('|', $value);

        return str_replace(' ', '', array_pop($statuses));
    }

    /**
     * Return order status mapping based on current configuration
     *
     * @param $status
     *
     * @return mixed
     */
    public function getStatusOrder($status, $statusDetail, $isCanCreditMemo)
    {
        switch ($status) {
            case 'approved': {
                $status = $this->scopeConfig->getValue('payment/mercadopago/order_status_approved', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
                if ($statusDetail == 'partially_refunded' && $isCanCreditMemo) {
                    $status = $this->scopeConfig->getValue('payment/mercadopago/order_status_partially_refunded', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
                }
                break;
            }
            case 'refunded': {
                $status = $this->scopeConfig->getValue('payment/mercadopago/order_status_refunded', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
                break;
            }
            case 'in_mediation': {
                $status = $this->scopeConfig->getValue('payment/mercadopago/order_status_in_mediation', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
                break;
            }
            case 'cancelled': {
                $status = $this->scopeConfig->getValue('payment/mercadopago/order_status_cancelled', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
                break;
            }
            case 'rejected': {
                $status = $this->scopeConfig->getValue('payment/mercadopago/order_status_rejected', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
                break;
            }
            case 'chargeback': {
                $status = $this->scopeConfig->getValue('payment/mercadopago/order_status_chargeback', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
                break;
            }
            default: {
                $status = $this->scopeConfig->getValue('payment/mercadopago/order_status_in_process', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
            }
        }

        return $status;
    }

    /**
     * Get the assigned state of an order status
     *
     * @param string $status
     */
    public function _getAssignedState($status)
    {
        $collection = $this->_statusFactory
            ->joinStates()
            ->addFieldToFilter('main_table.status', $status);

        $collectionItems = $collection->getItems();

        return array_pop($collectionItems)->getState();
    }

    /**
     * Return raw message for payment detail
     *
     * @param $status
     * @param $payment
     *
     * @return \Magento\Framework\Phrase|string
     */
    public function getMessage($status, $payment)
    {
        $rawMessage = __($this->_messageInterface->getMessage($status));
        $rawMessage .= __('<br/> Payment id: %1', $payment['id']);
        $rawMessage .= __('<br/> Status: %1', $payment['status']);
        $rawMessage .= __('<br/> Status Detail: %1', $payment['status_detail']);

        return $rawMessage;
    }

    /**
     * Returns status that must be set to order, if a not final status exists
     * then the last of this statuses is returned. Else the last of final statuses
     * is returned
     *
     * @param $dataStatus
     * @param $merchantOrder
     *
     * @return string
     */
    public function getStatusFinal($dataStatus, $merchantOrder)
    {
        //if (isset($merchantOrder['paid_amount']) && $merchantOrder['total_amount'] == $merchantOrder['paid_amount']) {
        //  return 'approved';
        //}
        if ($merchantOrder['total_amount'] == $merchantOrder['paid_amount']) {
            return 'approved';
        }
        $payments = $merchantOrder['payments'];
        $statuses = explode('|', $dataStatus);
        foreach ($statuses as $status) {
            $status = str_replace(' ', '', $status);
            if (in_array($status, $this->_notFinalStatus)) {
                $lastPaymentIndex = $this->_getLastPaymentIndex($payments, $this->_notFinalStatus);

                return $payments[$lastPaymentIndex]['status'];
            }
        }

        $lastPaymentIndex = $this->_getLastPaymentIndex($payments, $this->_finalStatus);

        return $payments[$lastPaymentIndex]['status'];
    }

    //--------------------- BEGIN todo modularizar

    //public function getDataPayments($merchantOrderData)
    //{
    //    $data = array();
    //    foreach ($merchantOrderData['payments'] as $payment) {
    //        $data = $this->_getFormattedPaymentData($payment['id'], $data);
    //    }
    //
    //    return $data;
    //}
    //
    //protected function _getFormattedPaymentData($paymentId, $data = [])
    //{
    //    $response = $this->_core->getPayment($paymentId);
    //    if ($response['status'] == 400 || $response['status'] == 401) {
    //        return [];
    //    }
    //    $payment = $response['response']['collection'];
    //
    //    return $this->formatArrayPayment($data, $payment, self::LOG_FILE);
    //}

    //--------------------- END to modularizar

    /**
     * @param $payments
     * @param $status
     *
     * @return int
     */
    protected function _getLastPaymentIndex($payments, $status)
    {
        $dates = [];
        foreach ($payments as $key => $payment) {
            if (in_array($payment['status'], $status)) {
                $dates[] = ['key' => $key, 'value' => $payment['last_modified']];
            }
        }
        usort($dates, ['MercadoPago\Core\Controller\Notifications\Standard', "_dateCompare"]);
        if ($dates) {
            $lastModified = array_pop($dates);

            return $lastModified['key'];
        }

        return 0;
    }

    /**
     * @param $merchantOrder
     *
     * @return array
     */
    public function getShipmentsArray($merchantOrder)
    {
        return (isset($merchantOrder['shipments'][0])) ? $merchantOrder['shipments'][0] : [];
    }

    /**
     * @param $payment \Magento\Sales\Model\Order\Payment
     */
    public function generateCreditMemo($payment, $order = null)
    {
        if (empty($order)) {
            $order = $this->_orderFactory->create()->loadByIncrementId($payment["order_id"]);
        }

        if ($payment['amount_refunded'] == $payment['total_paid_amount']) {
            $this->_createCreditmemo($order, $payment);
            $order->setForcedCanCreditmemo(false);
            $order->setActionFlag('ship', false);
            $order->save();
        } else {
            $this->_createCreditmemo($order, $payment);
        }
    }

    /**
     * @var $order      \Magento\Sales\Model\Order
     * @var $creditMemo \Magento\Sales\Model\Order\Creditmemo
     * @var $payment    \Magento\Sales\Model\Order\Payment
     */
    protected function _createCreditmemo($order, $data)
    {
        $order->setExternalRequest(true);
        $creditMemos = $order->getCreditmemosCollection()->getItems();

        $previousRefund = 0;
        foreach ($creditMemos as $creditMemo) {
            $previousRefund = $previousRefund + $creditMemo->getGrandTotal();
        }
        $amount = $data['amount_refunded'] - $previousRefund;
        if ($amount > 0) {
            $order->setExternalType('partial');
            $creditmemo = $this->_creditmemoFactory->createByOrder($order, [-1]);
            if (count($creditMemos) > 0) {
                $creditmemo->setAdjustmentPositive($amount);
            } else {
                $creditmemo->setAdjustmentNegative($amount);
            }
            $creditmemo->setGrandTotal($amount);
            $creditmemo->setBaseGrandTotal($amount);
            //status "Refunded" for creditMemo
            $creditmemo->setState(2);
            $creditmemo->getResource()->save($creditmemo);
            $order->setTotalRefunded($data['amount_refunded']);
            $order->getResource()->save($order);
        }
    }

    /**
     * Collect data from notification content to update order info
     *
     * @param $data
     * @param $payment
     *
     * @return mixed
     */
    public function formatArrayPayment($data, $payment, $logName)
    {
        $this->_dataHelper->log("Format Array", $logName);

        $fields = [
            "status",
            "status_detail",
            "order_id",
            "id",
            "payment_method_id",
            "transaction_amount",
            "total_paid_amount",
            "coupon_amount",
            "installments",
            "shipping_cost",
            "amount_refunded"
        ];

        foreach ($fields as $field) {
            if (isset($payment[$field])) {
                if (isset($data[$field])) {
                    $data[$field] .= " | " . $payment[$field];
                } else {
                    $data[$field] = $payment[$field];
                }
            }
        }

        if (isset($payment['refunds'])) {
            foreach ($payment['refunds'] as $refund) {
                if (isset($data['refunds'])) {
                    $data['refunds'] .= " | " . $refund['id'];
                } else {
                    $data['refunds'] = $refund['id'];
                }
            }
        }

        $data = $this->_updateAtributesData($data, $payment);

        $data['external_reference'] = $payment['external_reference'];
        $data['payer_first_name'] = $payment['payer']['first_name'];
        $data['payer_last_name'] = $payment['payer']['last_name'];
        $data['payer_email'] = $payment['payer']['email'];

        if (isset($data['payer_identification_type'])) {
            $data['payer_identification_type'] .= " | " . $payment['payer']['identification']['type'];
        } else {
            $data['payer_identification_type'] = $payment['payer']['identification']['type'];
        }

        if (isset($data['payer_identification_number'])) {
            $data['payer_identification_number'] .= " | " . $payment['payer']['identification']['number'];
        } else {
            $data['payer_identification_number'] = $payment['payer']['identification']['number'];
        }

        return $data;
    }

    protected function _updateAtributesData($data, $payment)
    {
        if (isset($payment["last_four_digits"])) {
            if (isset($data["trunc_card"])) {
                $data["trunc_card"] .= " | " . "xxxx xxxx xxxx " . $payment["last_four_digits"];
            } else {
                $data["trunc_card"] = "xxxx xxxx xxxx " . $payment["last_four_digits"];
            }
        }

        if (isset($payment['cardholder']['name'])) {
            if (isset($data["cardholder_name"])) {
                $data["cardholder_name"] .= " | " . $payment["cardholder"]["name"];
            } else {
                $data["cardholder_name"] = $payment["cardholder"]["name"];
            }
        }

        if (isset($payment['statement_descriptor'])) {
            $data['statement_descriptor'] = $payment['statement_descriptor'];
        }

        if (isset($payment['merchant_order_id'])) {
            $data['merchant_order_id'] = $payment['merchant_order_id'];
        }

        return $data;
    }

    /**
     * Updates order status ond creates invoice
     *
     * @param      $payment
     * @param null $stateObject
     *
     * @return array
     */
    public function setStatusOrder($payment)
    {
        $order = $this->_coreHelper->_getOrder($payment["external_reference"]);

        $statusDetail = $payment['status_detail'];
        $status = $payment['status'];

        if (isset($payment['status_final'])) {
            $status = $payment['status_final'];
        }
        $message = $this->getMessage($status, $payment);
        if ($this->isStatusUpdated()) {
            return ['text' => $message, 'code' => \MercadoPago\Core\Helper\Response::HTTP_OK];
        }

        //if state is not complete updates according to setting
        $this->_updateStatus($order, $status, $message, $statusDetail);

        $statusSave = $order->save();
        $this->_dataHelper->log("Update order", 'mercadopago.log', $statusSave->getData());
        $this->_dataHelper->log($message, 'mercadopago.log');

        try {
            $infoPayments = $order->getPayment()->getAdditionalInformation();
            if ($this->_getMulticardLastValue($status) == 'approved') {
                $this->_handleTwoCards($payment, $infoPayments);

                $this->_dataHelper->setOrderSubtotals($payment, $order);
                $this->_createInvoice($order, $message);

                //Associate card to customer
                $additionalInfo = $order->getPayment()->getAdditionalInformation();
                if (isset($additionalInfo['token'])) {
                    $order->getPayment()->getMethodInstance()->customerAndCards($additionalInfo['token'], $payment);
                }


            } elseif ($status == 'refunded' || $status == 'cancelled') {
                $order->setExternalRequest(true);
                $order->cancel();
            }

            return ['text' => $message, 'code' => \MercadoPago\Core\Helper\Response::HTTP_OK];
        } catch (\Exception $e) {
            $this->_dataHelper->log("erro in set order status: " . $e, 'mercadopago.log');

            return ['text' => $e, 'code' => \MercadoPago\Core\Helper\Response::HTTP_BAD_REQUEST];
        }
    }

    protected function _handleTwoCards(&$payment, $infoPayments)
    {
        if (isset($infoPayments['second_card_token']) && !empty($infoPayments['second_card_token'])) {
            $payment['total_paid_amount'] = $infoPayments['total_paid_amount'];
            $payment['transaction_amount'] = $infoPayments['transaction_amount'];
            $payment['status'] = $infoPayments['status'];
        }
    }

    protected function _createInvoice($order, $message)
    {
        if (!$order->hasInvoices()) {
            $invoice = $order->prepareInvoice();
            $invoice->register();
            $invoice->pay();
            $this->_transactionFactory->create()
                ->addObject($invoice)
                ->addObject($invoice->getOrder())
                ->save();

            $this->_invoiceSender->send($invoice, true, $message);
        }
    }

    /**
     * @param $order        \Magento\Sales\Model\Order
     * @param $statusHelper \MercadoPago\Core\Helper\StatusUpdate
     * @param $status
     * @param $message
     * @param $statusDetail
     */
    protected function _updateStatus($order, $status, $message, $statusDetail)
    {
        if ($order->getState() !== \Magento\Sales\Model\Order::STATE_COMPLETE) {
            $statusOrder = $this->getStatusOrder($status, $statusDetail, $order->canCreditmemo());

            $order->setState($this->_getAssignedState($statusOrder));
            $order->addStatusToHistory($statusOrder, $message, true);
            if (!$order->getEmailSent()){
                $this->_orderSender->send($order, true, $message);
            }
        }
    }
    /**
     * Set order and payment info
     *
     * @param $data
     */
    public function updateOrder($data, $order = null)
    {
        $this->_dataHelper->log("Update Order", 'mercadopago-notification.log');
        if (!$this->isStatusUpdated()) {
            try {
                if (!$order) {
                    $order = $this->_coreHelper->_getOrder($data["external_reference"]);
                }

                //update payment info
                $paymentOrder = $order->getPayment();
                $paymentAdditionalInfo = $paymentOrder->getAdditionalInformation();

                $additionalFields = [
                    'status',
                    'status_detail',
                    'id',
                    'transaction_amount',
                    'cardholderName',
                    'installments',
                    'statement_descriptor',
                    'trunc_card',
                    'payer_identification_type',
                    'payer_identification_number'

                ];


                foreach ($additionalFields as $field) {
                    if (isset($data[$field]) && empty($paymentAdditionalInfo['second_card_token'])) {
                        $paymentOrder->setAdditionalInformation($field, $data[$field]);
                    }
                }

                if (isset($data['id'])) {
                    $paymentOrder->setAdditionalInformation('payment_id_detail', $data['id']);
                }

                if (isset($data['payer_identification_type']) & isset($data['payer_identification_number'])) {
                    $paymentOrder->setAdditionalInformation($data['payer_identification_type'], $data['payer_identification_number']);
                }

                if (isset($data['payment_method_id'])) {
                    $paymentOrder->setAdditionalInformation('payment_method', $data['payment_method_id']);
                }

                if (isset($data['merchant_order_id'])) {
                    $paymentOrder->setAdditionalInformation('merchant_order_id', $data['merchant_order_id']);
                }

                $paymentStatus = $paymentOrder->save();
                $this->_dataHelper->log("Update Payment", 'mercadopago.log', $paymentStatus->getData());

                $statusSave = $order->save();
                $this->_dataHelper->log("Update order", 'mercadopago.log', $statusSave->getData());
            } catch (\Exception $e) {
                $this->_dataHelper->log("erro in update order status: " . $e, 'mercadopago.log');
                $this->getResponse()->setBody($e);

                //if notification proccess returns error, mercadopago will resend the notification.
                $this->getResponse()->setHttpResponseCode(\MercadoPago\Core\Helper\Response::HTTP_BAD_REQUEST);
            }
        }
    }

}