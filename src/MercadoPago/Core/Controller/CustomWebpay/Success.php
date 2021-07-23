<?php

namespace MercadoPago\Core\Controller\CustomWebpay;

use Exception;
use Throwable;
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
            $body = (array) json_decode($body);

            if (!$quoteId || !$body) {
                throw new Exception(__('Sorry, we can\'t process: missing params.'));
            }

            $token = $body['token'];
            $issuerId = $body['issuer_id'];
            $installments = $body['installments'];
            $paymentMethodId = $body['payment_method_id'];

            $preference = $this->webpayPayment->makePreference($token, $paymentMethodId, $issuerId, $installments);

            return;
        } catch (Throwable $e) {
            $this->messageManager->addExceptionMessage($e, __('Sorry, we can\'t finish Mercado Pago Webpay Payment.'));

            $this->_helperData->log('CustomPaymentWebpay - exception: ' . $e->getMessage(), self::LOG_NAME);

            return $this->resultRedirectFactory->create()->setPath('checkout/cart');
        }
    }
}
