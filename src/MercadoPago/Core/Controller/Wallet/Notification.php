<?php

namespace MercadoPago\Core\Controller\Wallet;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use MercadoPago\Core\Model\Preference\Wallet;

/**
 * Class Notification
 * @package MercadoPago\Core\Controller\Wallet
 */
class Notification extends AbstractAction
{
    const HTTP_RESPONSE_NO_CONTENT = 204;

    /**
     * Notification constructor.
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param Wallet $walletPreference
     */
    public function __construct(Context $context, JsonFactory $resultJsonFactory, Wallet $walletPreference)
    {
        parent::__construct($context, $resultJsonFactory, $walletPreference);
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $response = $this->resultJsonFactory->create();
        try {
            $id = $this->getRequest()->getParam('id', false);
            $topic = $this->getRequest()->getParam('topic', false);

            if ($topic !== 'merchant_order' || !$id) {
                return $response->setHttpResponseCode(self::HTTP_RESPONSE_NO_CONTENT);
            }

            $result = $this->walletPreference->processNotification($id);

            return $response->setData($result);
        } catch (\Exception $exception) {
            return $this->getErrorResponse($response, $exception->getTraceAsString());
        }
    }
}
