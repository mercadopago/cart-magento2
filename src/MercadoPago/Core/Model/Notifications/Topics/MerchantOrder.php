<?php

namespace MercadoPago\Core\Model\Notifications\Topics;

use Exception;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Sales\Model\Order\CreditmemoFactory;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Order\Email\Sender\OrderCommentSender;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\Order\Payment\TransactionFactory;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\ResourceModel\Status\Collection as StatusFactory;
use MercadoPago\Core\Helper\Data as mpHelper;
use MercadoPago\Core\Helper\Message\MessageInterface;
use MercadoPago\Core\Model\Core;
use MercadoPago\Core\Model\Notifications\Notifications;
use Magento\Framework\DB\Transaction;
use Magento\Sales\Model\Service\InvoiceService;

class MerchantOrder extends TopicsAbstract
{
    const LOG_NAME = 'notification_merchant_order';
    const TYPES_TOPIC = ['payment', 'merchant_order'];

    protected $_mpHelper;
    protected $_scopeConfig;
    protected $_coreModel;
    protected $_payAmount = 0;
    protected $_payIndex = 0;

    /**
     * MerchantOrder constructor.
     * @param mpHelper $mpHelper
     * @param ScopeConfigInterface $scopeConfig
     * @param Core $coreModel
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
        mpHelper $mpHelper,
        ScopeConfigInterface $scopeConfig,
        Core $coreModel,
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
        $this->_mpHelper = $mpHelper;
        $this->_scopeConfig = $scopeConfig;
        $this->_coreModel = $coreModel;

        parent::__construct($scopeConfig, $mpHelper, $orderFactory, $creditmemoFactory, $messageInterface, $statusFactory, $orderSender, $orderCommentSender, $transactionFactory, $invoiceSender, $invoiceService, $transaction);
    }

    /**
     * @param $id
     * @param null $type
     * @return array
     */
    public function getPaymentData($id, $type = null)
    {
        try {
            if ($type == Notifications::TYPE_NOTIFICATION_WEBHOOK) {
                $response = $this->_coreModel->getPayment($id);
                if (empty($response) || ($response['status'] != 200 && $response['status'] != 201)) {
                    throw new Exception(__('MP API PAYMENT Invalid Response'), 400);
                }
                $id = $response['order']['id'];
            }

            $response = $this->_coreModel->getMerchantOrder($id);
            $this->_mpHelper->log("Response API MP merchant_order", self::LOG_NAME, $response);
            if (!$this->isValidResponse($response)) {
                throw new Exception(__('MP API Invalid Response'), 400);
            }

            $merchantOrder = $response['response'];
            if (count($merchantOrder['payments']) == 0) {
                throw new Exception(__('MP API Payments Not Found'), 400);
            }

            $payments = [];
            foreach ($merchantOrder['payments'] as $payment) {
                $response = $this->_coreModel->getPayment($payment['id']);
                if (empty($response) || !isset($response['response'])) {
                    throw new Exception(__('MP API Payments Not Found in API'), 400);
                }
                $payments[] = $response['response'];
            }

            $shipmentData = (isset($merchantOrder['shipments'][0])) ? $merchantOrder['shipments'][0] : [];
            return ['merchantOrder' => $merchantOrder, 'payments' => $payments, 'shipmentData' => $shipmentData];
        } catch (\Exception $e) {
            $this->_mpHelper->log(__("ERROR - Notifications MerchantOrder getPaymentData"), self::LOG_NAME, $e->getMessage());
        }
    }

    /**
     * @param $payment
     * @param $key
     */
    public function payAmount($payment, $key)
    {
        if ($payment['status'] == 'approved') {
            $this->_payAmount += $payment['transaction_amount'];
            $this->_payIndex = $key;
        }
    }

    /**
     * @param $payments
     * @param $merchantOrder
     * @return array
     */
    public function getStatusFinal($payments, $merchantOrder)
    {
        $this->_payAmount = 0;
        $class = 'MercadoPago\Core\Model\Notifications\Topics\MerchantOrder';
        array_map([$class, 'payAmount'], $payments, array_keys($payments));

        if ($merchantOrder['total_amount'] <= $this->_payAmount) {
            return ['key' => $this->_payIndex, 'status' => 'approved', 'final' => true];
        }

        $notFinalStatus = ['authorized', 'process', 'in_mediation'];
        $paymentsOrder = $merchantOrder['payments'];
        foreach ($payments as $payment) {
            if (in_array($payment['status'], $notFinalStatus)) {
                $lastPaymentIndex = $this->_getLastPaymentIndex($paymentsOrder, $notFinalStatus);
                return ['key' => $this->_payIndex, 'status' => $payments[$lastPaymentIndex]['status'], 'final' => false];
            }
        }

        $finalStatus = ['rejected', 'cancelled', 'refunded', 'charge_back'];
        $lastPaymentIndex = $this->_getLastPaymentIndex($payments, $finalStatus);

        return ['key' => $this->_payIndex, 'status' => $payments[$lastPaymentIndex]['status'], 'final' => true];
    }

    /**
     * @param $payments
     * @param $status
     * @return int
     */
    protected function _getLastPaymentIndex($payments, $status)
    {
        $class = 'MercadoPago\Core\Model\Notifications\Topics\MerchantOrder';
        $dates = [];
        foreach ($payments as $key => $payment) {
            if (in_array($payment['status'], $status)) {
                $dates[] = ['key' => $key, 'value' => $payment['date_last_updated']];
            }
        }
        usort($dates, [$class, "_dateCompare"]);
        if ($dates) {
            $lastModified = array_pop($dates);
            return $lastModified['key'];
        }

        return 0;
    }

    /**
     * @param $order
     * @param $data
     * @return bool|void
     * @throws Exception
     */
    public function updateOrder($order, $data)
    {
        $this->_dataHelper->log("Merchant Order - Update Order", 'mercadopago-basic.log');
        $order = parent::updateOrder($order, $data);
        $this->_dataHelper->log("Merchant Order - Update Order", 'mercadopago-basic.log', $order->getData());
    }

    /**
     * @param $a
     * @param $b
     * @return false|int
     */
    public static function _dateCompare($a, $b)
    {
        $t1 = strtotime($a['value']);
        $t2 = strtotime($b['value']);
        return $t2 - $t1;
    }
}
