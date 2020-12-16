<?php

namespace MercadoPago\Core\Controller\Wallet;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Webapi\Exception;
use MercadoPago\Core\Model\Preference\Wallet;

/**
 * Class Preference
 * @package MercadoPago\Core\Controller\Wallet
 */
class Preference extends Action
{
    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var Wallet
     */
    protected $walletPreference;

    /**
     * Preference constructor.
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param Wallet $walletPreference
     */
    public function __construct(Context $context, JsonFactory $resultJsonFactory, Wallet $walletPreference)
    {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->walletPreference = $walletPreference;
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
        } catch (\Exception $exception) {
            $this->messageManager->addExceptionMessage(
                $exception,
                __('We can\'t start Mercado Pago Wallet Payment.')
            );

            return $this->getErrorResponse($response);
        }

        return $response;
    }

    /**
     * @param Json $response
     * @return Json
     */
    protected function getErrorResponse(Json $response)
    {
        $response->setHttpResponseCode(Exception::HTTP_BAD_REQUEST);
        $response->setData(['message' => __('Sorry, but something went wrong when starts Mercado Pago Wallet')]);

        return $response;
    }
}
