<?php

namespace MercadoPago\Core\Controller\CustomWebpay;

use Exception;
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
     * Success constructor
     *
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param Payment $webpayPayment
     * @param MercadopagoData $helperData
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        Payment $webpayPayment,
        MercadopagoData $helperData
    ) {
        parent::__construct($context, $resultJsonFactory, $webpayPayment, $helperData);
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        try {
            $quoteId = $this->getRequest()->getParam('quote_id', false);

            $body = $this->getRequest()->getContent();
            $body = explode('&', $body);

            $content = [];
            foreach ($body as $value) {
                $value = explode('=', $value);
                $content[$value[0]] = $value[1];
            }

            if (empty($quoteId) || empty($body) || empty($content)) {
                throw new Exception('Webpay callback error: missing params');
            }

            if ($content['status'] >= 200 && $content['status'] <= 299) {
                $this->helperData->log('CustomPaymentWebpay - callback error', self::LOG_NAME, $content);
                return $this->resultRedirectFactory->create()->setPath('mercadopago/customwebpay/failure');
            }

            $token           = $content['token'];
            $issuerId        = $content['issuer_id'];
            $installments    = $content['installments'];
            $paymentMethodId = $content['payment_method_id'];

            $payment = $this->webpayPayment->createPayment(
                $quoteId,
                $token,
                $paymentMethodId,
                $issuerId,
                $installments
            );

            $this->webpayPayment->createOrder($payment['response']);

            return $this->resultRedirectFactory->create()->setPath('checkout/onepage/success');
        } catch (Exception $e) {
            $this->helperData->log('CustomPaymentWebpay - exception: ' . $e->getMessage(), self::LOG_NAME);
            return $this->resultRedirectFactory->create()->setPath('mercadopago/customwebpay/failure');
        }
    }
}
