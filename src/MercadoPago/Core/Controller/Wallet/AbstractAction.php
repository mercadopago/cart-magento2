<?php

namespace MercadoPago\Core\Controller\Wallet;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Webapi\Exception as ExceptionHttpCode;
use MercadoPago\Core\Model\Preference\Wallet;

/**
 * Class AbstractAction
 * @package MercadoPago\Core\Controller\Wallet
 */
abstract class AbstractAction extends Action
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
     * AbstractAction constructor.
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
     * @return ResponseInterface|ResultInterface
     */
    abstract public function execute();

    /**
     * @param Json $response
     * @param $message
     * @param int $code
     * @return Json
     */
    protected function getErrorResponse(Json $response, $message, $code = ExceptionHttpCode::HTTP_BAD_REQUEST)
    {
        $response->setHttpResponseCode($code);
        $response->setData(['message' => $message]);

        return $response;
    }
}
