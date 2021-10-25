<?php

namespace MercadoPago\Core\Controller\Notifications;

use Exception;
use Magento\Framework\App\Action\Context;
use MercadoPago\Core\Helper\Data;
use MercadoPago\Core\Helper\Response;
use MercadoPago\Core\Model\Core;
use MercadoPago\Core\Model\Notifications\Notifications;

class Custom extends NotificationBase
{
    const LOG_NAME = 'custom_notification';

    /**
     * @var Data
     */
    protected $coreHelper;

    /**
     * @var Core
     */
    protected $coreModel;

    /**
     * @var
     */
    protected $_order;

    /**
     * @var Notifications
     */
    protected $_notifications;

    /**
     * Custom constructor.
     *
     * @param Context $context
     * @param Data $coreHelper
     * @param Core $coreModel
     * @param Notifications $notifications
     */
    public function __construct(Context $context, Data $coreHelper, Core $coreModel, Notifications $notifications)
    {
        $this->coreHelper = $coreHelper;
        $this->coreModel = $coreModel;
        $this->_notifications = $notifications;
        parent::__construct($context);
    } //end __construct()

    /**
     * @return void
     */
    public function execute()
    {
        try {
            $request = $this->getRequest();

            $requestValues = $this->_notifications->validateRequest($request);
            $topicClass = $this->_notifications->getTopicClass($request);

            if ($requestValues['topic'] != 'payment') {
                $message = 'Mercado Pago - Invalid Notification Parameters, Invalid Type.';
                $this->setResponseHttp(Response::HTTP_BAD_REQUEST, $message, $request->getParams());
            }

            $response = $this->coreModel->getPaymentV1($requestValues['id']);

            if (empty($response) || ($response['status'] != 200 && $response['status'] != 201)) {
                $message = 'Mercado Pago - Payment not found, Mercado Pago API did not return the expected information.';
                $this->setResponseHttp(Response::HTTP_NOT_FOUND, $message, $response);
                return;
            }

            $payment = $response['response'];
            $response = $topicClass->updateStatusOrderByPayment($payment);

            $this->setResponseHttp($response['httpStatus'], $response['message'], $response['data']);

            return;
        } catch (Exception $e) {
            $statusResponse = Response::HTTP_INTERNAL_ERROR;

            if (method_exists($e, 'getCode') && ($e->getCode() >= 200 && $e->getCode() <= 599)) {
                $statusResponse = $e->getCode();
            }

            $message = 'Mercado Pago - There was a serious error processing the notification. Could not handle the error.';
            $this->setResponseHttp($statusResponse, $message, ['exception_error' => $e->getMessage()]);
        } //end try
    } //end execute()

    /**
     * @param $httpStatus
     * @param $message
     * @param array $data
     */
    protected function setResponseHttp($httpStatus, $message, $data = [])
    {
        $response = [
            'status' => $httpStatus,
            'message' => $message,
            'data' => $data,
        ];

        $this->coreHelper->log('NotificationsCustom::setResponseHttp - Response: ' . json_encode($response), self::LOG_NAME);

        $this->getResponse()->setHeader('Content-Type', 'application/json', $overwriteExisting = true);
        $this->getResponse()->setBody(json_encode($response));
        $this->getResponse()->setHttpResponseCode($httpStatus);
    } //end setResponseHttp()
}//end class
