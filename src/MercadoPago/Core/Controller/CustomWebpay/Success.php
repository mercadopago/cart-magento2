<?php

namespace MercadoPago\Core\Controller\CustomWebpay;

use Exception;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
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
     * Reserve constructor.
     *
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param Payment $webpayPayment
     * @param Session $session
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        Payment $webpayPayment,
        Session $session
    ) {
        $this->session = $session;
        parent::__construct($context, $resultJsonFactory, $webpayPayment);
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        try {
            $quoteId = $this->getRequest()->getParam('quote_id', false);

            if (!$quoteId) {
                throw new Exception(__('Sorry, we can\'t process the quote id not found'));
            }

            return $this->resultRedirectFactory->create()->setPath('checkout/onepage/success');
        } catch (Exception $exception) {
            $this->messageManager->addExceptionMessage(
                $exception,
                __('Sorry, we can\'t finish Mercado Pago Webpay Payment.')
            );

            return $this->resultRedirectFactory->create()->setPath('checkout/cart');
        }
    }
}
