<?php

namespace MercadoPago\Core\Model\Notifications\Topics;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DB\TransactionFactory;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\CreditmemoFactory;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Order\Email\Sender\OrderCommentSender;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\ResourceModel\Status\Collection as StatusFactory;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Store\Model\ScopeInterface;
use MercadoPago\Core\Helper\ConfigData;
use MercadoPago\Core\Helper\Data;
use MercadoPago\Core\Helper\Message\MessageInterface;
use MercadoPago\Core\Helper\Response;
use MercadoPago\Core\Helper\Round;
use MercadoPago\Core\Model\Transaction;

abstract class TopicsAbstract
{
    public $_statusUpdatedFlag;

    /**
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var Data
     */
    protected $_dataHelper;

    /**
     * @var OrderFactory
     */
    protected $_orderFactory;

    /**
     * @var CreditmemoFactory
     */
    protected $_creditmemoFactory;

    /**
     * @var MessageInterface
     */
    protected $_messageInterface;

    /**
     * @var StatusFactory
     */
    protected $_statusFactory;

    /**
     * @var OrderSender
     */
    protected $_orderSender;

    /**
     * @var OrderCommentSender
     */
    protected $_orderCommentSender;

    /**
     * @var TransactionFactory
     */
    protected $_transactionFactory;

    /**
     * @var InvoiceSender
     */
    protected $_invoiceSender;

    /**
     * @var InvoiceService
     */
    protected $_invoiceService;

    /**
     * @var Transaction
     */
    private $_transaction;

    /**
     * TopicsAbstract constructor.
     * @param ScopeConfigInterface $scopeConfig
     * @param Data $dataHelper
     * @param OrderFactory $orderFactory
     * @param CreditmemoFactory $creditmemoFactory
     * @param MessageInterface $messageInterface
     * @param StatusFactory $statusFactory
     * @param OrderSender $orderSender
     * @param OrderCommentSender $orderCommentSender
     * @param TransactionFactory $transactionFactory
     * @param InvoiceSender $invoiceSender
     * @param InvoiceService $invoiceService
     * @param Transaction $transaction
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Data $dataHelper,
        OrderFactory $orderFactory,
        CreditmemoFactory $creditmemoFactory,
        MessageInterface $messageInterface,
        StatusFactory $statusFactory,
        OrderSender $orderSender,
        OrderCommentSender $orderCommentSender,
        TransactionFactory $transactionFactory,
        InvoiceSender $invoiceSender,
        InvoiceService $invoiceService,
        Transaction $transaction
    ) {
        $this->_dataHelper = $dataHelper;
        $this->_scopeConfig = $scopeConfig;
        $this->_orderFactory = $orderFactory;
        $this->_creditmemoFactory = $creditmemoFactory;
        $this->_messageInterface = $messageInterface;
        $this->_statusFactory = $statusFactory;
        $this->_orderSender = $orderSender;
        $this->_orderCommentSender = $orderCommentSender;
        $this->_transactionFactory = $transactionFactory;
        $this->_invoiceSender = $invoiceSender;
        $this->_invoiceService = $invoiceService;
        $this->_transaction = $transaction;
    }

    /**
     * @param $incrementId
     * @return Order
     */
    public function getOrderByIncrementId($incrementId)
    {
        return $this->_orderFactory->create()->loadByIncrementId($incrementId);
    }

    /**
     * @param $paymentResponse
     * @return string
     */
    public function getMessage($paymentResponse)
    {
        $rawMessage = __($this->_messageInterface->getMessage($paymentResponse['status']));
        $rawMessage .= __('<br/> Payment id: %1', $paymentResponse['id']);
        $rawMessage .= __('<br/> Status: %1', $paymentResponse['status']);
        $rawMessage .= __('<br/> Status Detail: %1', $paymentResponse['status_detail']);

        return $rawMessage;
    }

    /**
     * @param $order
     * @param $invoice
     * @return string
     */
    public function getMessageInvoice($order, $invoice)
    {
        $rawMessage = __('<br/> Order id: %1', $order->getIncrementId());
        $rawMessage .= __('<br/> Invoice ID: %1', $invoice->getId());
        $rawMessage .= __('<br/> Total Invoiced: %1', $invoice->getGrandTotal());
        return $rawMessage;
    }

    /**
     * @param $payment
     * @param $isCanCreditMemo
     * @return mixed
     */
    public function getConfigStatus($payment, $isCanCreditMemo)
    {
        $pathStatus = "PATH_ORDER_" . strtoupper($payment['status']);
        $path = constant('\MercadoPago\Core\Helper\ConfigData::' . $pathStatus);
        $status = $this->_scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE);
        if ($payment['status'] == 'approved') {
            if ($payment['status_detail'] == 'partially_refunded' && $isCanCreditMemo) {
                $status = $this->_scopeConfig->getValue(ConfigData::PATH_ORDER_PARTIALLY_REFUNDED, ScopeInterface::SCOPE_STORE);
            }
        }

        if (empty($status)) {
            $status = $this->_scopeConfig->getValue(ConfigData::PATH_ORDER_PENDING, ScopeInterface::SCOPE_STORE);
        }

        return $status;
    }

    /**
     * @param $order
     * @param $newStatusOrder
     * @param $message
     * @return Order
     */
    public function setStatusAndComment($order, $newStatusOrder, $message)
    {
        if ($order->getState() !== Order::STATE_COMPLETE) {
            if ($newStatusOrder == 'canceled' && $order->getState() != 'canceled') {
                $order->cancel();
            } else {
                $order->setState($this->_getAssignedState($newStatusOrder));
            }
            $order->addStatusToHistory($newStatusOrder, $message, true);
        }

        return $order;
    }

    /**
     * @param $status
     * @return mixed
     */
    public function _getAssignedState($status)
    {
        $collection = $this->_statusFactory->joinStates()->addFieldToFilter('main_table.status', $status);
        $collectionItems = $collection->getItems();
        return array_pop($collectionItems)->getState();
    }

    /**
     * @param $response
     * @return bool
     */
    public function isValidResponse($response): bool
    {
        if (!isset($response['status'])) {
            return false;
        }

        if ($response['status'] == 200 && $response['status'] == 201) {
            return true;
        }

        if (!isset($response['response'])) {
            return false;
        }

        return true;
    }

    /**
     * @param $order
     * @param $data
     * @return bool
     * @throws AlreadyExistsException
     */
    public function validateRefunded($order, $data)
    {
        $merchantOrder = $data['merchantOrder'];
        if (isset($merchantOrder["amount_refunded"]) && $merchantOrder["amount_refunded"] > 0) {
            $creditMemo = $this->generateCreditMemo($data, $order);
            if (empty($creditMemo)) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param $payment
     * @param $order
     * @return Order\Creditmemo|null
     * @throws AlreadyExistsException
     */
    public function generateCreditMemo($payment, $order)
    {
        $creditmemo = null;
        $order->setExternalRequest(true);
        $creditMemos = $order->getCreditmemosCollection()->getItems();

        $previousRefund = 0;
        foreach ($creditMemos as $creditMemo) {
            $previousRefund = $previousRefund + $creditMemo->getGrandTotal();
        }

        $amount = $payment['amount_refunded'] - $previousRefund;
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
            $creditmemo->setState(2);
            $creditmemo->getResource()->save($creditmemo);

            $order->setTotalRefunded($payment['amount_refunded']);
            $order->getResource()->save($order);
        }

        if ($payment['amount_refunded'] == $payment['total_paid_amount']) {
            $order->setForcedCanCreditmemo(false);
            $order->setActionFlag('ship', false);
            $order->save();
        }

        return $creditmemo;
    }

    /**
     * @param $order
     * @param $data
     * @return bool
     */
    public function updateOrder($order, $data)
    {
        if ($this->checkStatusAlreadyUpdated($order, $data)) {
            $this->_dataHelper->log("Already updated", 'mercadopago-basic.log');
            return $order;
        }
        $this->updatePaymentInfo($order, $data);
        return $order->save();
    }

    /**
     * @param $order
     * @param $data
     */
    public function updatePaymentInfo($order, $data)
    {
        $paymentOrder = $order->getPayment();
        $paymentAdditionalInfo = $paymentOrder->getAdditionalInformation();
        $dataPayment = $data['payments'][$data['statusFinal']['key']];

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
            if (isset($dataPayment[$field]) && empty($paymentAdditionalInfo['second_card_token'])) {
                $paymentOrder->setAdditionalInformation($field, $dataPayment[$field]);
            }
        }

        if (isset($dataPayment['id'])) {
            $paymentOrder->setAdditionalInformation('payment_id_detail', $dataPayment['id']);
        }

        if (isset($dataPayment['payer']['identification']['type']) & isset($dataPayment['payer']['identification']['number'])) {
            $paymentOrder->setAdditionalInformation($dataPayment['payer']['identification']['type'], $dataPayment['payer']['identification']['number']);
        }

        if (isset($dataPayment['payment_method_id'])) {
            $paymentOrder->setAdditionalInformation('payment_method', $dataPayment['payment_method_id']);
        }

        if (isset($dataPayment['order']['id'])) {
            $paymentOrder->setAdditionalInformation('merchant_order_id', $dataPayment['order']['id']);
        }

        $paymentStatus = $paymentOrder->save();
        $this->_dataHelper->log("Update Payment", 'mercadopago-basic.log', $paymentStatus->getData());
    }

    /**
     * @param $paymentResponse
     * @param $order
     * @return bool
     */
    public function checkStatusAlreadyUpdated($paymentResponse, $order)
    {
        $orderUpdated = false;
        $statusToUpdate = $this->getConfigStatus($paymentResponse, false);
        $commentsObject = $order->getStatusHistoryCollection(true);
        foreach ($commentsObject as $commentObj) {
            if ($commentObj->getStatus() == $statusToUpdate) {
                $orderUpdated = true;
            }
        }

        return $orderUpdated;
    }

    /**
     * @param $order
     * @param $data
     * @return array
     */
    public function changeStatusOrder($order, $data)
    {
        $payment = $data['payments'][$data['statusFinal']['key']];
        $message = $this->getMessage($payment);

        if ($this->_statusUpdatedFlag) {
            return ['text' => $message, 'code' => Response::HTTP_OK];
        }

        $this->updateStatus($order, $payment, $message);

        try {
            $infoPayments = $order->getPayment()->getAdditionalInformation();
            if ($this->_getMulticardLastValue($payment['status']) == 'approved') {
                $this->_handleTwoCards($payment, $infoPayments);
                $this->_dataHelper->setOrderSubtotals($payment, $order);
                $this->_createInvoice($order);
                if (isset($payment['metadata']) && isset($payment['metadata']['token'])) {
                    $order->getPayment()->getMethodInstance()->customerAndCards($payment['metadata']['token'], $payment);
                }
            } elseif ($payment['status'] == 'refunded' || $payment['status'] == 'cancelled') {
                $order->setExternalRequest(true);
                $order->cancel();
            }

            return ['text' => $message, 'code' => Response::HTTP_OK];
        } catch (\Exception $e) {
            $this->_dataHelper->log("Error in setOrderStatus: " . $e, 'mercadopago-basic.log');
            return ['text' => $e, 'code' => Response::HTTP_BAD_REQUEST];
        }
    }

    /**
     * @param $order
     * @param $payment
     * @param $message
     * @return mixed
     */
    public function updateStatus($order, $payment, $message)
    {
        if ($order->getState() !== Order::STATE_COMPLETE) {
            $statusOrder = $this->getConfigStatus($payment, $order->canCreditmemo());
            $orderTotal  = Round::roundWithSiteId($order->getGrandTotal(), $this->getSiteId());
            $couponMP = $payment['coupon_amount'];
            $paidTotal = $payment['transaction_details']['total_paid_amount'];

            if ($couponMP > 0) {
                $paidTotal += $couponMP;
            }

            if ($orderTotal > $paidTotal) {
                $statusOrder = 'fraud';
                $message .= __('<br/> Order total: %1', $order->getGrandTotal());
                $message .= __('<br/> Paid: %1', $paidTotal);
            }

            $emailAlreadySent = false;
            $emailOrderCreate = $this->_scopeConfig->getValue(
                ConfigData::PATH_ADVANCED_EMAIL_CREATE,
                ScopeInterface::SCOPE_STORE
            );

            if ($statusOrder == 'canceled') {
                $order->cancel();
            } else {
                $order->setState($this->_getAssignedState($statusOrder));
            }

            if ($this->_scopeConfig->isSetFlag(ConfigData::PATH_ADVANCED_SAVE_TRANSACTION, ScopeInterface::SCOPE_STORE)) {
                $paymentOrder = $order->getPayment();
                $this->_transaction->update($paymentOrder, $order, $payment['status']);
            }

            $order->addStatusToHistory($statusOrder, $message, true);
            if ($emailOrderCreate) {
                if (!$order->getEmailSent()) {
                    $this->_orderSender->send($order, true);
                    $emailAlreadySent = true;
                }
            }
        }

        $this->_dataHelper->log("Update order", 'mercadopago-basic.log', $order->getData());
        $this->_dataHelper->log($message, 'mercadopago-basic.log');

        return $order->save();
    }


    /**
     * @param $value
     * @return mixed
     */
    public function _getMulticardLastValue($value)
    {
        $statuses = explode('|', $value);
        return str_replace(' ', '', array_pop($statuses));
    }

    /**
     * @param $payment
     * @param $infoPayments
     */
    public function _handleTwoCards(&$payment, $infoPayments)
    {
        if (isset($infoPayments['second_card_token']) && !empty($infoPayments['second_card_token'])) {
            $payment['total_paid_amount'] = $infoPayments['total_paid_amount'];
            $payment['transaction_amount'] = $infoPayments['transaction_amount'];
            $payment['status'] = $infoPayments['status'];
        }
    }

    /**
     * @param $order
     * @return bool
     * @throws LocalizedException
     */
    public function _createInvoice($order)
    {
        if (!$order->hasInvoices()) {
            $invoice = $this->_invoiceService->prepareInvoice($order);
            $invoice->register();
            $invoice->pay();
            $invoice->save();

            $transaction = $this->_transactionFactory->create();
            $transaction->addObject($invoice);
            $transaction->addObject($invoice->getOrder());
            $transaction->save();

            $this->_invoiceSender->send($invoice);

            return true;
        }

        return false;
    }

    /**
     * @param $order
     * @param $message
     */
    public function sendEmailCreateOrUpdate($order, $message)
    {
        $emailOrderCreate = $this->_scopeConfig->getValue(ConfigData::PATH_ADVANCED_EMAIL_CREATE, ScopeInterface::SCOPE_STORE);
        $emailAlreadySent = false;

        if ($emailOrderCreate) {
            if (!$order->getEmailSent()) {
                $this->_orderSender->send($order, true);
                $emailAlreadySent = true;
            }
        }

        if ($emailAlreadySent === false) {
            $statusEmail = $this->_scopeConfig->getValue(ConfigData::PATH_ADVANCED_EMAIL_UPDATE, ScopeInterface::SCOPE_STORE);
            $statusEmailList = explode(',', $statusEmail);
            if (in_array($order->getStatus(), $statusEmailList)) {
                $this->_orderCommentSender->send($order, $notify = '1', str_replace('<br/>', '', $message));
            }
        }
    } //end sendEmailCreateOrUpdate()

    /**
     * @return false|string|string[]
     */
    protected function getSiteId()
    {
        return mb_strtoupper($this->_scopeConfig->getValue(
            \MercadoPago\Core\Helper\ConfigData::PATH_SITE_ID,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        ));
    }//end getSiteId()
}
