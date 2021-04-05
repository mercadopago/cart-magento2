<?php

namespace MercadoPago\Core\Model\Notifications;

use Exception;
use Magento\Framework\App\Config\ScopeConfigInterface;
use MercadoPago\Core\Helper\Data as mpHelper;
use MercadoPago\Core\Helper\Response;
use MercadoPago\Core\Model\Notifications\Topics\MerchantOrder;
use MercadoPago\Core\Model\Notifications\Topics\Payment;

class Notifications
{
    const LOG_NAME = 'Notifications';
    const TYPES_TOPIC = ['payment', 'merchant_order'];
    const TYPE_NOTIFICATION_WEBHOOK = 'webhook';
    const TYPE_NOTIFICATION_IPN = 'ipn';

    protected $merchant_order;
    protected $payment;
    protected $_mpHelper;
    protected $_scopeConfig;

    /**
     * Notifications constructor.
     * @param mpHelper $mpHelper
     * @param ScopeConfigInterface $scopeConfig
     * @param MerchantOrder $merchantOrder
     * @param Payment $payment
     */
    public function __construct(mpHelper $mpHelper, ScopeConfigInterface $scopeConfig, MerchantOrder $merchantOrder, Payment $payment)
    {
        $this->_mpHelper = $mpHelper;
        $this->_scopeConfig = $scopeConfig;
        $this->merchant_order = $merchantOrder;
        $this->payment = $payment;
    }

    /**
     * @param $request
     * @return array
     * @throws Exception
     */
    public function validateRequest($request)
    {
        $this->_mpHelper->log("Received notification", self::LOG_NAME, $request->getParams());

        if (!is_null($request->getParam('topic')) && $request->getParam('topic') == 'payment') {
            throw new Exception(__('Is accepted only Topic Merchant Order (IPN) and Payment Type (Webhook).'), Response::HTTP_OK);
        }

        empty($request->getParam('topic')) ? $topic = $request->getParam('type') : $topic = $request->getParam('topic');
        if (empty($topic)) {
            throw new Exception(__('Request param TOPIC not found'), Response::HTTP_BAD_REQUEST);
        }

        if (!in_array($topic, self::TYPES_TOPIC)) {
            throw new Exception(__('Invalid param TOPIC'), Response::HTTP_BAD_REQUEST);
        }
        $type = empty($request->getParam('topic')) ? self::TYPE_NOTIFICATION_WEBHOOK : self::TYPE_NOTIFICATION_IPN;
        empty($request->getParam('id')) ? $id = $request->getParam('data_id') : $id = $request->getParam('id');
        if (empty($id)) {
            throw new Exception(__('Request param ID not found'), Response::HTTP_BAD_REQUEST);
        }

        return ['id' => $id, 'topic' => $topic, 'type' => $type];
    }

    /**
     * @param $request
     * @return MerchantOrder|Payment
     */
    public function getTopicClass($request)
    {

        $class = $this->merchant_order;

        if (!is_null($request->getParam('type')) && $request->getParam('type') == 'payment') {
            $class = $this->payment;
        }

        return $class;
    }

    /**
     * @param $class
     * @param $request
     * @return mixed
     */
    public function getPaymentInformation($class, $request)
    {
        return $class->getPaymentData($request['id'], $request['type']);
    }
}
