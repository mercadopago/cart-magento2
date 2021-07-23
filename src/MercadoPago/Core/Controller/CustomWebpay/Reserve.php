<?php

namespace MercadoPago\Core\Controller\CustomWebpay;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use MercadoPago\Core\Model\CustomWebpay\Payment;

/**
 * Class Reserve
 *
 * @package MercadoPago\Core\Controller\CustomWebpay
 */
class Reserve extends AbstractAction
{
    /**
     * Reserve constructor.
     *
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param Payment $webpayPayment
     */
    public function __construct(Context $context, JsonFactory $resultJsonFactory, Payment $webpayPayment)
    {
        parent::__construct($context, $resultJsonFactory, $webpayPayment);
    }

    /**
     * View page action
     *
     * @return ResultInterface
     */
    public function execute()
    {
        $response = $this->resultJsonFactory->create();

        try {
            $this->webpayPayment->reserveQuote();

            $response->setData(
                [
                    'quote_id' => $this->webpayPayment->getReservedQuoteId(),
                ]
            );

            return $response;
        } catch (\Exception $exception) {
            $this->messageManager->addExceptionMessage(
                $exception,
                __('Sorry, we can\'t reserve quote on Webpay Payment.')
            );

            return $this->getErrorResponse($response, $exception->getMessage());
        }
    }
}
