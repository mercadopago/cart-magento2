<?php

namespace MercadoPago\Core\Controller\Wallet;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use MercadoPago\Core\Model\Preference\Wallet;

class Success extends AbstractAction
{
    /**
     * @var Session
     */
    protected $session;

    /**
     * Success constructor.
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param Wallet $walletPreference
     * @param Session $session
     */
    public function __construct(Context $context, JsonFactory $resultJsonFactory, Wallet $walletPreference, Session $session)
    {
        $this->session = $session;
        parent::__construct($context, $resultJsonFactory, $walletPreference);
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $paymentId = $this->getRequest()->getParam('payment_id');
        $response = $this->walletPreference->getMercadoPagoInstance()->get("/v1/payments/{$paymentId}");
        $payment = $response['response'];
        $this->session->setLastSuccessQuoteId($payment['metadata']['quote_id']);
        $this->session->setLastQuoteId($payment['metadata']['quote_id']);
        $this->session->setLastOrderId($payment['external_reference']);
        $this->session->setLastRealOrderId($payment['external_reference']);
        $this->quote = $this->session->getQuote();
        $this->quote->getPayment()->setMethod('mercado_pago_custom');

        return $this->resultRedirectFactory->create()->setPath('checkout/onepage/success');
    }
}
