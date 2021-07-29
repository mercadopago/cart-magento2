<?php

namespace MercadoPago\Core\Controller\CustomWebpay;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use MercadoPago\Core\Helper\Data as MercadopagoData;
use MercadoPago\Core\Model\CustomWebpay\Payment;

/**
 * Class Success
 *
 * @package MercadoPago\Core\Controller\CustomWebpay
 */
class Success extends AbstractAction
{
    /**
     * @var Session
     */
    protected $session;

    /**
     * Success constructor.
     *
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param Payment $webpayPayment
     * @param MercadopagoData $helperData
     * @param Session $session
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        Payment $webpayPayment,
        MercadopagoData $helperData,
        Session $session
    ) {
        $this->session = $session;
        parent::__construct($context, $resultJsonFactory, $webpayPayment, $helperData);
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        try {
            $body = $this->getRequest()->getContent();
            $body = explode('&', $body);

            $content = [];
            foreach ($body as $value) {
                $value = explode('=', $value);
                $content[$value[0]] = $value[1];
            }

            if (!isset($body) || empty($content)) {
                throw new \Exception('Webpay callback error: missing params');
            }

            if ($content['status'] > 299) {
                $this->helperData->log('CustomPaymentWebpay - callback error', self::LOG_NAME, $content);
                return $this->resultRedirectFactory->create()->setPath('mercadopago/customwebpay/failure');
            }

            $token           = $content['token'];
            $issuerId        = $content['issuer_id'];
            $installments    = $content['installments'];
            $paymentMethodId = $content['payment_method_id'];

            $payment = $this->webpayPayment->createPayment($token, $paymentMethodId, $issuerId, $installments);
            $this->webpayPayment->createOrder($payment['response']);

            return $this->resultRedirectFactory->create()->setPath('checkout/onepage/success');
        } catch (\Exception $e) {
            $this->helperData->log('CustomPaymentWebpay - exception: ' . $e->getMessage(), self::LOG_NAME);
            return $this->resultRedirectFactory->create()->setPath('mercadopago/customwebpay/failure');
        }
    }
}
