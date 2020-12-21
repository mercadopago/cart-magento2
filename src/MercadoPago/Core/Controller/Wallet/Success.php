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
        try {
            $paymentId = $this->getRequest()->getParam('payment_id', false);

            if (!$paymentId) {
                throw new \Exception('Payment ID not found');
            }

            $this->walletPreference->processSuccessRequest($paymentId, $this->session);
            return $this->resultRedirectFactory->create()->setPath('checkout/onepage/success');
        } catch (\Throwable $exception) {
            $this->messageManager->addExceptionMessage($exception->getMessage());
            return $this->resultRedirectFactory->create()->setPath('checkout/cart');
        }
    }
}
