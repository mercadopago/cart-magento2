<?php
namespace MercadoPago\Core\Controller\Notifications;

/**
 * Class Standard
 *
 * @package MercadoPago\Core\Controller\Notifications
 */
class Standard
    extends \Magento\Framework\App\Action\Action

{
    /**
     * @var \MercadoPago\Core\Model\Standard\PaymentFactory
     */
    protected $_paymentFactory;

    /**
     * @var \MercadoPago\Core\Helper\
     */
    protected $coreHelper;

    /**
     * @var \MercadoPago\Core\Model\Core
     */
    protected $coreModel;

    /**
     *log file name
     */
    const LOG_NAME = 'standard_notification';

    protected $_finalStatus = ['rejected', 'cancelled', 'refunded', 'charge_back'];
    protected $_notFinalStatus = ['authorized', 'process', 'in_mediation'];

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $_orderFactory;

    protected $_creditmemoFactory;

    /**
     * Standard constructor.
     *
     * @param \Magento\Framework\App\Action\Context           $context
     * @param \MercadoPago\Core\Model\Standard\PaymentFactory $paymentFactory
     * @param \MercadoPago\Core\Helper\Data                   $coreHelper
     * @param \MercadoPago\Core\Model\Core                    $coreModel
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \MercadoPago\Core\Model\Standard\PaymentFactory $paymentFactory,
        \MercadoPago\Core\Helper\Data $coreHelper,
        \MercadoPago\Core\Model\Core $coreModel,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Sales\Model\Order\CreditmemoFactory $creditmemoFactory
    )
    {
        $this->_paymentFactory = $paymentFactory;
        $this->coreHelper = $coreHelper;
        $this->coreModel = $coreModel;
        $this->_orderFactory = $orderFactory;
        $this->_creditmemoFactory = $creditmemoFactory;
        parent::__construct($context);
    }

    protected function _emptyParams($p1, $p2)
    {
        return (empty($p1) || empty($p2));
    }

    protected function _isValidResponse($response)
    {
        return ($response['status'] == 200 || $response['status'] == 201);
    }

    protected function _responseLog()
    {
        $this->coreHelper->log("Http code", self::LOG_NAME, $this->getResponse()->getHttpResponseCode());
    }

    protected function _getShipmentsArray($merchantOrder)
    {
        return (isset($merchantOrder['shipments'][0])) ? $merchantOrder['shipments'][0] : [];
    }

    protected function _getFormattedPaymentData($paymentId, $data = [])
    {
        $response = $this->coreModel->getPayment($paymentId);
        $payment = $response['response']['collection'];

        return $this->_formatArrayPayment($data, $payment);
    }

    protected function _shipmentExists($shipmentData, $merchantOrder)
    {
        return (!empty($shipmentData) && !empty($merchantOrder));
    }

    /**
     * Controller Action
     */
    public function execute()
    {
        $request = $this->getRequest();
        //notification received
        $this->coreHelper->log("Standard Received notification", self::LOG_NAME, $request->getParams());

        $shipmentData = '';
        $merchantOrder = '';
        $id = $request->getParam('id');
        $topic = $request->getParam('topic');

        if ($this->_emptyParams($id, $topic)) {
            $this->coreHelper->log("Merchant Order not found", self::LOG_NAME, $request->getParams());
            $this->getResponse()->setBody("Merchant Order not found");
            $this->getResponse()->setHttpResponseCode(\MercadoPago\Core\Helper\Response::HTTP_NOT_FOUND);

            return;
        }

        if ($topic == 'merchant_order') {
            $response = $this->coreModel->getMerchantOrder($id);
            $this->coreHelper->log("Return merchant_order", self::LOG_NAME, $response);
            if (!$this->_isValidResponse($response)) {
                $this->_responseLog();

                return;
            }

            $merchantOrder = $response['response'];
            if (count($merchantOrder['payments']) == 0) {
                $this->_responseLog();

                return;
            }
            $data = $this->_getDataPayments($merchantOrder);
            $statusFinal = $this->_getStatusFinal($data['status'], $merchantOrder);
            $shipmentData = $this->_getShipmentsArray($merchantOrder);

        } elseif ($topic == 'payment') {
            $data = $this->_getFormattedPaymentData($id);
            $statusFinal = $data['status'];
        } else {
            $this->_responseLog();

            return;
        }

        // if this happens, we need to generate a credit memo
        if (isset($data["amount_refunded"]) && $data["amount_refunded"] > 0) {
            $this->_generateCreditMemo($data);
        }

        $this->coreHelper->log("Update Order", self::LOG_NAME);
        $this->coreHelper->setStatusUpdated($data);
        $this->coreModel->updateOrder($data);

        if ($this->_shipmentExists($shipmentData, $merchantOrder)) {
            $this->_eventManager->dispatch(
                'mercadopago_standard_notification_before_set_status',
                ['shipmentData' => $shipmentData, 'orderId' => $merchantOrder['external_reference']]
            );
        }

        if ($statusFinal != false) {
            $data['status_final'] = $statusFinal;
            $this->coreHelper->log("Received Payment data", self::LOG_NAME, $data);
            $setStatusResponse = $this->coreModel->setStatusOrder($data);
            $this->getResponse()->setBody($setStatusResponse['text']);
            $this->getResponse()->setHttpResponseCode($setStatusResponse['code']);
        } else {
            $this->getResponse()->setBody("Status not final");
            $this->getResponse()->setHttpResponseCode(\MercadoPago\Core\Helper\Response::HTTP_OK);
        }
        if ($this->_shipmentExists($shipmentData, $merchantOrder)) {
            $this->_eventManager->dispatch('mercadopago_standard_notification_received',
                ['payment'        => $data,
                 'merchant_order' => $merchantOrder]
            );
        }

        $this->_responseLog();

    }

    /**
     * @param $payment \Magento\Sales\Model\Order\Payment
     */
    protected function _generateCreditMemo($payment)
    {
        $order = $this->_orderFactory->create()->loadByIncrementId($payment["order_id"]);

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
     * @var $order \Magento\Sales\Model\Order
     * @var $creditMemo \Magento\Sales\Model\Order\Creditmemo
     * @var $payment \Magento\Sales\Model\Order\Payment
     */
    protected function _createCreditmemo ($order, $data)
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
     * Collect data from notification content
     *
     * @param $merchantOrder
     *
     * @return array
     */
    protected function _getDataPayments($merchantOrder)
    {
        $data = array();
        foreach ($merchantOrder['payments'] as $payment) {
            $response = $this->coreModel->getPayment($payment['id']);
            $payment = $response['response']['collection'];
            $data = $this->_formatArrayPayment($data, $payment);
        }

        return $data;
    }


    /**
     * Collect data from notification content to update order info
     *
     * @param $data
     * @param $payment
     *
     * @return mixed
     */
    protected function _formatArrayPayment($data, $payment)
    {
        $this->coreHelper->log("Format Array", self::LOG_NAME);

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
            "refunds",
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

        $data['external_reference'] = $payment['external_reference'];
        $data['payer_first_name'] = $payment['payer']['first_name'];
        $data['payer_last_name'] = $payment['payer']['last_name'];
        $data['payer_email'] = $payment['payer']['email'];

        return $data;
    }

    protected function _dateCompare($a, $b)
    {
        $t1 = strtotime($a['value']);
        $t2 = strtotime($b['value']);

        return $t2 - $t1;
    }

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
     * Returns status that must be set to order, if a not final status exists
     * then the last of this statuses is returned. Else the last of final statuses
     * is returned
     *
     * @param $dataStatus
     * @param $merchantOrder
     *
     * @return string
     */
    protected function _getStatusFinal($dataStatus, $merchantOrder)
    {
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

}