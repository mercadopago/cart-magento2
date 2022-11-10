<?php

namespace MercadoPago\Core\Model\Notifications\Topics;

use Exception;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DB\TransactionFactory;
use Magento\Sales\Model\Order\CreditmemoFactory;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Order\Email\Sender\OrderCommentSender;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\ResourceModel\Status\Collection as StatusFactory;
use Magento\Sales\Model\Service\InvoiceService;
use MercadoPago\Core\Helper\Data as mpHelper;
use MercadoPago\Core\Helper\Message\MessageInterface;
use MercadoPago\Core\Helper\Response;
use MercadoPago\Core\Model\Core;
use MercadoPago\Core\Model\Notifications\Notifications;
use MercadoPago\Core\Model\Transaction;

class MerchantOrder extends TopicsAbstract
{
    const LOG_NAME = 'notification_merchant_order';

    const TYPES_TOPIC = ['payment', 'merchant_order'];

    /**
     * @var mpHelper
     */
    protected $_mpHelper;

    /**
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var Core
     */
    protected $_coreModel;

    /**
     * @var int
     */
    protected $_payAmount = 0;

    /**
     * @var int
     */
    protected $_payIndex = 0;

    /**
     * MerchantOrder constructor.
     *
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
     * @return array|void
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

            if ($merchantOrder['status'] != 'closed') {
                throw new Exception(__('Payments Not Finalized'), 400);
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
        } catch (Exception $e) {
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
        if (isset($merchantOrder['payments']) && count($merchantOrder['payments']) == 1) {
            return ['key' => "0", 'status' => $merchantOrder['payments'][0]['status'], 'final' => false];
        }

        $totalApproved = 0;
        $totalPending = 0;
        $payments = $merchantOrder['payments'];
        $totalOrder = $merchantOrder['total_amount'];
        foreach ($payments as $payment) {
            $status = $payment['status'];

            if ($status == 'approved') {
                $totalApproved += $payment['transaction_amount'];
            } elseif ($status == 'in_process' || $status == 'pending' || $status == 'authorized') {
                $totalPending += $payment['transaction_amount'];
            }
        }

        $totals = [
            "totalApproved" => $totalApproved,
            "totalOrder" => $totalOrder,
            "totalPending" => $totalPending
        ];

        if ($totalApproved >= $totalOrder) {
            $statusList = ['approved'];
            $lastPaymentIndex = $this->_getLastPaymentIndex($payments, $statusList);

            $response = ['key' => $lastPaymentIndex, 'status' => 'approved', 'final' => true, 'totals' => $totals];
            $this->_dataHelper->log("Order Setted Approved: " . json_encode($totals), 'mercadopago-basic.log', $response);
        } elseif ($totalPending >= $totalOrder) {
            $statusList = ['pending', 'in_process'];
            $lastPaymentIndex = $this->_getLastPaymentIndex($payments, $statusList);

            $response = ['key' => $lastPaymentIndex, 'status' => 'pending', 'final' => false, 'totals' => $totals];
            $this->_dataHelper->log("Order Setted Pending: " . json_encode($totals), 'mercadopago-basic.log', $response);
        } else {
            $statusList = ['cancelled', 'refunded', 'charged_back', 'in_mediation', 'rejected'];
            $lastPaymentIndex = $this->_getLastPaymentIndex($payments, $statusList);
            $statusReturned = $payments[$lastPaymentIndex]['status'];

            $response = ['key' => $lastPaymentIndex, 'status' => $payments[$lastPaymentIndex]['status'], 'final' => true, 'totals' => $totals];
            $this->_dataHelper->log("Order Setted Other Status: " . $statusReturned, 'mercadopago-basic.log', $response);
        }

        return $response;
    }

    /**
     * @param $payments
     * @param $status
     * @return int|mixed|string
     */
    protected function _getLastPaymentIndex($payments, $status)
    {
        $class = 'MercadoPago\Core\Model\Notifications\Topics\MerchantOrder';
        $dates = [];

        foreach ($payments as $key => $payment) {
            if (in_array($payment['status'], $status)) {
                $dates[] = ['key' => $key, 'value' => $payment['last_modified']];
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
     * @return array
     */
    public function updateOrder($order, $data)
    {
        if (count($data['payments']) > 1) {
            $data['payments'][$data['statusFinal']['key']]['transaction_details']['total_paid_amount'] =
                $data['statusFinal']['totals']['totalApproved'];
        }

        $payment = $data['payments'][$data['statusFinal']['key']];
        $orderPayment = $order->getPayment();
        $orderPayment->setAdditionalInformation("paymentResponse", $payment);
        $orderPayment->save();

        if ($this->checkStatusAlreadyUpdated($order, $data)) {
            $message = "[Already updated] " . $this->getMessage($payment);
            $this->_dataHelper->log($message, 'mercadopago-basic.log');
            $this->sendEmailCreateOrUpdate($order, $message);
            return ['text' => $message, 'code' => Response::HTTP_OK];
        }

        $this->updatePaymentInfo($order, $data);

        return $this->changeStatusOrder($order, $data);
    }

    public function checkStatusAlreadyUpdated($order, $data)
    {
        $paymentResponse = $data['payments'][$data['statusFinal']['key']];
        return parent::checkStatusAlreadyUpdated($paymentResponse, $order);
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
