<?php

namespace MercadoPago\Core\Controller\CustomWebpay;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use MercadoPago\Core\Model\CustomWebpay\Payment;
use MercadoPago\Core\Helper\Data as MercadopagoData;

/**
 * Class Success
 *
 * @package MercadoPago\Core\Controller\CustomWebpay
 */
class Success extends AbstractAction
{
    /**
     * log filename
     */
    const LOG_NAME = 'custom_webpay';

    /**
     * @var Session
     */
    protected $session;

    /**
     * @var MercadopagoData
     */
    protected $_helperData;

    /**
     * Reserve constructor.
     *
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param Payment $webpayPayment
     * @param Session $session
     * @param MercadopagoData $helperData
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        Payment $webpayPayment,
        Session $session,
        MercadopagoData $helperData
    ) {
        $this->session = $session;
        $this->helperData = $helperData;
        parent::__construct($context, $resultJsonFactory, $webpayPayment);
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
            
            if (!isset($quoteId) || empty($quoteId) || !isset($body) || empty($content)) {
                throw new \Exception('Webpay callback error: missing params');
            }

            if ($content['status'] > 299) {
                throw new \Exception(
                    'Webpay callback error: ' . $content['error'] .
                    ' - status: ' . $content['status'] .
                    ' - cause: ' . $content['cause'] .
                    ' - message: '  . $content['message']
                );
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
        } catch (\Throwable $e) {
            $this->messageManager->addExceptionMessage($e, __('Sorry, we can\'t finish Mercado Pago Webpay Payment.'));
            $this->helperData->log('CustomPaymentWebpay - exception: ' . $e->getMessage(), self::LOG_NAME);

            return $this->resultRedirectFactory->create()->setPath('mercadopago/customwebpay/failure');
        }
    }
}
