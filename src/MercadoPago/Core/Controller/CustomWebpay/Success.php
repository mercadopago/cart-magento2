<?php

namespace MercadoPago\Core\Controller\CustomWebpay;

use Exception;
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
                throw new Exception(__('Sorry, we can\'t process: missing params.'));
            }

            $token           = $content['token'];
            $issuerId        = $content['issuer_id'];
            $installments    = $content['installments'];
            $paymentMethodId = $content['payment_method_id'];

            $preference = $this->webpayPayment->makePreference($token, $paymentMethodId, $issuerId, $installments);

            var_dump($preference);

            // return;
        } catch (Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('Sorry, we can\'t finish Mercado Pago Webpay Payment.'));

            $this->helperData->log('CustomPaymentWebpay - exception: ' . $e->getMessage(), self::LOG_NAME);

            // return $this->resultRedirectFactory->create()->setPath('checkout/cart');
        }
    }
}
