<?php

namespace MercadoPago\Core\Controller\Standard;

use Exception;

/**
 * Class Pay action controller to pay order with MP
 *
 * @package Mercadopago\Core\Controller\Standard
 */
class Pay extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \MercadoPago\Core\Model\Standard\PaymentFactory
     */
    protected $_paymentFactory;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @param \Magento\Framework\App\Action\Context              $context
     * @param \MercadoPago\Core\Model\Standard\PaymentFactory    $paymentFactory
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \MercadoPago\Core\Model\Standard\PaymentFactory $paymentFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->_paymentFactory = $paymentFactory;
        $this->_scopeConfig = $scopeConfig;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Redirect|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $typeCheckout = $this->_scopeConfig->getValue('payment/mercadopago_standard/type_checkout', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $resultRedirect = $this->resultRedirectFactory->create();
        $array_assign = $this->_getPostPago();

        if ($array_assign['status'] != 400) {
            $resultRedirect->setUrl($array_assign['init_point']);
        } else {
            if ($typeCheckout != 'lightbox') {
                $resultRedirect->setUrl($this->_url->getUrl('mercadopago/standard/failure'));
            }
            $resultRedirect->setUrl($this->_url->getUrl('mercadopago/standard/failureRedirect'));
        }
        return $resultRedirect;
    }

    /**
     * @return mixed
     */
    private function _getPostPago()
    {
        try {
            $standard = $this->_paymentFactory->create();
            $array_assign = $standard->postPago();
            return $array_assign;
        } catch (Exception $e) {
            $error = ['status' => 400];
            return $error;
        }
    }
}
