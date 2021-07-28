<?php

namespace MercadoPago\Core\Controller\CustomWebpay;

use Exception;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use MercadoPago\Core\Model\CustomWebpay\Payment;
use MercadoPago\Core\Helper\Data as MercadopagoData;

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
     * @{@inheritDoc}
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
        } catch (Exception $exception) {
            $this->messageManager->addExceptionMessage(
                $exception,
                __('Sorry, we can\'t reserve quote on Webpay Payment.')
            );

            return $this->getErrorResponse($response, $exception->getMessage());
        }
    }
}
