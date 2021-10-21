<?php

namespace MercadoPago\Core\Controller\Wallet;

use Magento\Framework\App\Action\Context;
use MercadoPago\Core\Controller\Notifications\NotificationBase;
use MercadoPago\Core\Model\Notifications\Notifications;
use MercadoPago\Core\Model\Preference\Wallet;
use Throwable;

class Notification extends NotificationBase
{
    const HTTP_RESPONSE_NOT_FOUND = 404;

    const HTTP_RESPONSE_BAD_REQUEST = 400;

    const HTTP_RESPONSE_INTERNAL_ERROR = 500;

    /**
     * @var Wallet
     */
    protected $wallet;

    /**
     * @var Notifications
     */
    protected $notifications;

    /**
     * Notification constructor.
     * @param Context $context
     * @param Wallet $wallet
     * @param Notifications $notifications
     */
    public function __construct(
        Context       $context,
        Wallet        $wallet,
        Notifications $notifications
    )
    {
        parent::__construct($context);
        $this->notifications = $notifications;
        $this->wallet = $wallet;
    }

    /**
     * @return void
     */
    public function execute()
    {
        $request = $this->getRequest();

        try {
            $requestValues = $this->notifications->validateRequest($request);
            $topicClass = $this->notifications->getTopicClass($request);

            $data = $this->notifications->getPaymentInformation($topicClass, $requestValues);
            if (empty($data)) {
                throw new \Exception(
                    __('Error Merchant Order notification is expected'),
                    self::HTTP_RESPONSE_NOT_FOUND
                );
            }

            $merchantOrder = $data['merchantOrder'];
            if ($merchantOrder === null) {
                throw new \Exception(
                    __('Merchant Order not found or is an notification invalid type.'),
                    self::HTTP_RESPONSE_NOT_FOUND
                );
            }

            $order = $this->wallet->processNotification($merchantOrder);
            if ($order->getStatus() === 'canceled') {
                throw new \Exception(
                    __('Order already canceled: ') . $merchantOrder["external_reference"],
                    self::HTTP_RESPONSE_BAD_REQUEST
                );
            }

            $data['statusFinal'] = $topicClass->getStatusFinal($data['payments'], $merchantOrder);
            if (!$topicClass->validateRefunded($order, $data)) {
                throw new \Exception(__('Error Order Refund'), self::HTTP_RESPONSE_BAD_REQUEST);
            }

            $statusResponse = $topicClass->updateOrder($order, $data);

            $this->setResponseHttp($statusResponse['code'], $statusResponse['text'], $request->getParams());
        } catch (Throwable $exception) {
            $code = $exception->getCode();

            if ($exception->getCode() < 200 || $exception->getCode() > 500) {
                $code = self::HTTP_RESPONSE_INTERNAL_ERROR;
            }

            $this->setResponseHttp($code, $exception->getMessage(), $request->getParams());
        }
    }

    /**
     * @param $httpStatus
     * @param $message
     * @param array $data
     */
    protected function setResponseHttp($httpStatus, $message, $data = [])
    {
        $response = [
            "status" => $httpStatus,
            "message" => $message,
            "data" => $data
        ];

        $this->getResponse()->setHeader('Content-Type', 'application/json', true);
        $this->getResponse()->setBody(json_encode($response));
        $this->getResponse()->setHttpResponseCode($httpStatus);
    }
}
