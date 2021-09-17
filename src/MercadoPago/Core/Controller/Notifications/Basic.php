<?php

namespace MercadoPago\Core\Controller\Notifications;

use Exception;
use Magento\Framework\App\Action\Context;
use Magento\Sales\Model\OrderFactory;
use MercadoPago\Core\Helper\Data;
use MercadoPago\Core\Model\Basic\Payment;
use MercadoPago\Core\Model\Core;
use MercadoPago\Core\Model\Notifications\Notifications;

class Basic extends NotificationBase
{
    const LOG_NAME = 'basic_notification';

    protected $_paymentFactory;

    protected $coreHelper;

    protected $coreModel;

    protected $_finalStatus = [
        'rejected',
        'cancelled',
        'refunded',
        'charged_back',
    ];

    protected $_notFinalStatus = [
        'authorized',
        'process',
        'in_mediation',
    ];

    protected $_orderFactory;

    protected $_notifications;

    /**
     * Basic constructor.
     *
     * @param Context       $context
     * @param Payment       $paymentFactory
     * @param Data          $coreHelper
     * @param Core          $coreModel
     * @param OrderFactory  $orderFactory
     * @param Notifications $notifications
     */
    public function __construct(
        Context $context,
        Payment $paymentFactory,
        Data $coreHelper,
        Core $coreModel,
        OrderFactory $orderFactory,
        Notifications $notifications
    ) {
        $this->_paymentFactory = $paymentFactory;
        $this->coreHelper      = $coreHelper;
        $this->coreModel       = $coreModel;
        $this->_orderFactory   = $orderFactory;
        $this->_notifications  = $notifications;
        parent::__construct($context);
    } //end __construct()

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
     */
    public function execute()
    {
        $request = $this->getRequest();

        try {
            $requestValues = $this->_notifications->validateRequest($request);
            $topicClass    = $this->_notifications->getTopicClass($request);

            $data          = $this->_notifications->getPaymentInformation($topicClass, $requestValues);
            if (empty($data)) {
                throw new Exception(__('Error Merchant Order notification is expected'), 400);
            }

            $merchantOrder = $data['merchantOrder'];
            if ($merchantOrder === null) {
                throw new Exception(__('Merchant Order not found or is an notification invalid type.'), 400);
            }

            $order = $this->_orderFactory->create()->loadByIncrementId($merchantOrder['external_reference']);
            if (empty($order) || empty($order->getId())) {
                throw new Exception(__('Error Order Not Found in Magento: ') . $merchantOrder['external_reference'], 400);
            }

            if ($order->getStatus() == 'canceled') {
                throw new Exception(__('Order already canceled: ')
                . $merchantOrder['external_reference'], 400);
            }

            $data['statusFinal'] = $topicClass->getStatusFinal($data['payments'], $merchantOrder);
            if (!$topicClass->validateRefunded($order, $data)) {
                throw new Exception(__('Error Order Refund'), 400);
            }

            $statusResponse = $topicClass->updateOrder($order, $data);

            $this->setResponseHttp($statusResponse['code'], $statusResponse['text'], $request->getParams());
        } catch (\Exception $e) {
            $this->setResponseHttp($e->getCode(), $e->getMessage(), $request->getParams());
        } //end try
    } //end execute()

    /**
     * @param $httpStatus
     * @param $message
     * @param array      $data
     */
    protected function setResponseHttp($httpStatus, $message, $data = [])
    {
        if ($httpStatus < 200 || $httpStatus > 500) {
            $httpStatus = 500;
        }

        $response = [
            'status'  => $httpStatus,
            'message' => $message,
            'data'    => $data,
        ];

        $this->coreHelper->log(
            'NotificationsBasic::setResponseHttp - Response: ' . json_encode($response),
             self::LOG_NAME
        );

        $this->getResponse()->setHeader('Content-Type', 'application/json', $overwriteExisting = true);
        $this->getResponse()->setBody(json_encode($response));
        $this->getResponse()->setHttpResponseCode($httpStatus);
    } //end setResponseHttp()
}//end class
