<?php

namespace MercadoPago\Core\Controller\Wallet;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use MercadoPago\Core\Model\Preference\Wallet;

class Preference extends AbstractAction
{
    /**
     * Preference constructor.
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param Wallet $walletPreference
     */
    public function __construct(Context $context, JsonFactory $resultJsonFactory, Wallet $walletPreference)
    {
        parent::__construct($context, $resultJsonFactory, $walletPreference);
    }

    /**
     * View  page action
     *
     * @return ResultInterface
     */
    public function execute()
    {
        $response = $this->resultJsonFactory->create();

        try {
            $preference = $this->walletPreference->makePreference();
            $response->setData(
                [
                    'status' => $preference['status'],
                    'preference' => [
                        'id' => $preference['response']['id']
                    ],
                ]
            );

            return $response;
        } catch (\Exception $exception) {
            $this->messageManager->addExceptionMessage(
                $exception,
                __('Sorry, we can\'t start Mercado Pago Wallet Payment.')
            );

            return $this->getErrorResponse($response, $exception->getMessage());
        }
    }
}
