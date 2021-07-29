<?php

namespace MercadoPago\Core\Controller\CustomWebpay;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use MercadoPago\Core\Helper\Data as MercadopagoData;
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
            $response->setData(
                [
                    'quote_id' => '000000',
                ]
            );

            return $response;
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage(
                $e,
                __('Sorry, we can\'t reserve quote on Webpay Payment.')
            );

            return $this->getErrorResponse($response, $e->getMessage());
        }
    }
}
