<?php

namespace MercadoPago\Core\Controller\CustomWebpay;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Webapi\Exception as ExceptionHttpCode;
use MercadoPago\Core\Model\CustomWebpay\Payment;

/**
 * Class AbstractAction
 * @package MercadoPago\Core\Controller\CustomWebpay
 */
abstract class AbstractAction extends Action
{
    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var Payment
     */
    protected $webpayPayment;

    /**
     * AbstractAction constructor.
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param Payment $webpayPayment
     */
    public function __construct(Context $context, JsonFactory $resultJsonFactory, Payment $webpayPayment)
    {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->webpayPayment = $webpayPayment;
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
