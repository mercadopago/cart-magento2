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

    /**
     * @var \MercadoPago\Core\Helper\StatusUpdate
     */
    protected $_statusHelper;
    protected $_order;

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
        \MercadoPago\Core\Helper\StatusUpdate $statusHelper,
        \MercadoPago\Core\Model\Core $coreModel,
        \Magento\Sales\Model\OrderFactory $orderFactory
    )
    {
        $this->_paymentFactory = $paymentFactory;
        $this->coreHelper = $coreHelper;
        $this->coreModel = $coreModel;
        $this->_orderFactory = $orderFactory;
        $this->_statusHelper = $statusHelper;
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

//_generateCreditMemo

    protected function _getFormattedPaymentData($paymentId, $data = [])
    {
        $response = $this->coreModel->getPayment($paymentId);
        $payment = $response['response']['collection'];

        return  $this->_statusHelper->formatArrayPayment($data, $payment, self::LOG_NAME);
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
            $statusFinal = $this->_statusHelper->getStatusFinal($data['status'], $merchantOrder);
            $shipmentData = $this->_statusHelper->getShipmentsArray($merchantOrder);

        } elseif ($topic == 'payment') {
            $data = $this->_getFormattedPaymentData($id);
            $statusFinal = $data['status'];
        } else {
            $this->_responseLog();

            return;
        }

        // if this happens, we need to generate a credit memo
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
                'mercadopago_standard_notification_before_set_status',
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
            $data = $this->_statusHelper->formatArrayPayment($data, $payment, self::LOG_NAME);
        }
        return $data;
    }

    public static function _dateCompare($a, $b)
    {
        $t1 = strtotime($a['value']);
        $t2 = strtotime($b['value']);

        return $t2 - $t1;
    }

    protected function _orderExists()
    {
        if ($this->_order->getId()) {
            return true;
        }
        $this->coreHelper->log(\MercadoPago\Core\Helper\Response::INFO_EXTERNAL_REFERENCE_NOT_FOUND, self::LOG_NAME, $this->_requestData->getParams());
        $this->getResponse()->getBody(\MercadoPago\Core\Helper\Response::INFO_EXTERNAL_REFERENCE_NOT_FOUND);
        $this->getResponse()->setHttpResponseCode(\MercadoPago\Core\Helper\Response::HTTP_NOT_FOUND);
        $this->coreHelper->log("Http code", self::LOG_NAME, $this->getResponse()->getHttpResponseCode());

        return false;
    }
}