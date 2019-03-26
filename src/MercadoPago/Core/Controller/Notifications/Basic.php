<?php

namespace MercadoPago\Core\Controller\Notifications;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Sales\Model\OrderFactory;
use MercadoPago\Core\Helper\Data;
use MercadoPago\Core\Helper\Response;
use MercadoPago\Core\Helper\StatusUpdate;
use MercadoPago\Core\Model\Basic\Payment;
use MercadoPago\Core\Model\Core;

class Basic extends Action
{
    const LOG_NAME = 'basic_notification';

    protected $_paymentFactory;
    protected $coreHelper;
    protected $coreModel;
    protected $_finalStatus = ['rejected', 'cancelled', 'refunded', 'charge_back'];
    protected $_notFinalStatus = ['authorized', 'process', 'in_mediation'];
    protected $_orderFactory;
    protected $_statusHelper;
    protected $_order;

    /**
     * Basic constructor.
     * @param Context $context
     * @param Payment $paymentFactory
     * @param Data $coreHelper
     * @param StatusUpdate $statusHelper
     * @param Core $coreModel
     * @param OrderFactory $orderFactory
     */
    public function __construct(
        Context $context,
        Payment $paymentFactory,
        Data $coreHelper,
        StatusUpdate $statusHelper,
        Core $coreModel,
        OrderFactory $orderFactory
    ) {
        $this->_paymentFactory = $paymentFactory;
        $this->coreHelper = $coreHelper;
        $this->coreModel = $coreModel;
        $this->_orderFactory = $orderFactory;
        $this->_statusHelper = $statusHelper;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
     */
    public function execute()
    {
        $shipmentData = '';
        $merchantOrder = '';

        $request = $this->getRequest();
        $id = $request->getParam('id');
        $topic = $request->getParam('topic');

        $this->coreHelper->log("Standard Received notification", self::LOG_NAME, $request->getParams());

        if ($this->_emptyParams($id, $topic)) {
            $this->coreHelper->log("Merchant Order not found", self::LOG_NAME, $request->getParams());
            $this->getResponse()->setBody("Merchant Order not found");
            $this->getResponse()->setHttpResponseCode(Response::HTTP_NOT_FOUND);
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
            $statusFinal = $this->_statusHelper->getStatusFinal($data['status'], $merchantOrder);
            $shipmentData = $this->_statusHelper->getShipmentsArray($merchantOrder);
        } elseif ($topic == 'payment') {
            $data = $this->_getFormattedPaymentData($id);
            $statusFinal = $data['status'];
        // $response = $this->coreModel->getPaymentV1($id);
            // $payment = $response['response'];
            // $payment = $this->coreHelper->setPayerInfo($payment);
        } else {
            $this->_responseLog();
            return;
        }

        if (isset($data["amount_refunded"]) && $data["amount_refunded"] > 0) {
            $this->_statusHelper->generateCreditMemo($data);
        }
        $this->_order = $this->coreModel->_getOrder($data['external_reference']);
        if (!$this->_orderExists() || $this->_order->getStatus() == 'canceled') {
            return;
        }
        $this->coreHelper->log("Update Order", self::LOG_NAME);
        $this->_statusHelper->setStatusUpdated($data, $this->_order);
        $this->_statusHelper->updateOrder($data, $this->_order);
        if ($this->_shipmentExists($shipmentData, $merchantOrder)) {
            $this->_eventManager->dispatch(
                'mercadopago_basic_notification_before_set_status',
                ['shipmentData' => $shipmentData, 'orderId' => $merchantOrder['external_reference']]
            );
        }
        if ($statusFinal != false) {
            $data['status_final'] = $statusFinal;
            $this->coreHelper->log("Received Payment data", self::LOG_NAME, $data);
            $setStatusResponse = $this->_statusHelper->setStatusOrder($data);
            $this->getResponse()->setBody($setStatusResponse['text']);
            $this->getResponse()->setHttpResponseCode($setStatusResponse['code']);
        } else {
            $this->getResponse()->setBody("Status not final");
            $this->getResponse()->setHttpResponseCode(Response::HTTP_OK);
        }
        if ($this->_shipmentExists($shipmentData, $merchantOrder)) {
            $this->_eventManager->dispatch(
                'mercadopago_standard_notification_received',
                ['payment' => $data,
                    'merchant_order' => $merchantOrder]
            );
        }
        $this->_responseLog();
    }

    /**
     * @param $p1
     * @param $p2
     * @return bool
     */
    protected function _emptyParams($p1, $p2)
    {
        return (empty($p1) || empty($p2));
    }

    /**
     * @param $response
     * @return bool
     */
    protected function _isValidResponse($response)
    {
        return ($response['status'] == 200 || $response['status'] == 201);
    }

    /**
     * Response Log
     */
    protected function _responseLog()
    {
        $this->coreHelper->log("Http code", self::LOG_NAME, $this->getResponse()->getHttpResponseCode());
    }

    /**
     * @param $paymentId
     * @param array $data
     * @return mixed
     */
    protected function _getFormattedPaymentData($paymentId, $data = [])
    {
        $response = $this->coreModel->getPayment($paymentId);
        $payment = $response['response'];
        return $this->_statusHelper->formatArrayPayment($data, $payment, self::LOG_NAME);
    }

    /**
     * @param $shipmentData
     * @param $merchantOrder
     * @return bool
     */
    protected function _shipmentExists($shipmentData, $merchantOrder)
    {
        return (!empty($shipmentData) && !empty($merchantOrder));
    }

    /**
     * @param $merchantOrder
     * @return array
     */
    protected function _getDataPayments($merchantOrder)
    {
        $data = [];
        foreach ($merchantOrder['payments'] as $payment) {
            $response = $this->coreModel->getPayment($payment['id']);
            $payment = $response['response'];
            $data = $this->_statusHelper->formatArrayPayment($data, $payment, self::LOG_NAME);
        }
        return $data;
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

    /**
     * @return bool
     */
    protected function _orderExists()
    {
        if ($this->_order->getId()) {
            return true;
        }
        $this->coreHelper->log(Response::INFO_EXTERNAL_REFERENCE_NOT_FOUND, self::LOG_NAME, $this->_requestData->getParams());
        $this->getResponse()->getBody(Response::INFO_EXTERNAL_REFERENCE_NOT_FOUND);
        $this->getResponse()->setHttpResponseCode(Response::HTTP_NOT_FOUND);
        $this->coreHelper->log("Http code", self::LOG_NAME, $this->getResponse()->getHttpResponseCode());
        return false;
    }
}
