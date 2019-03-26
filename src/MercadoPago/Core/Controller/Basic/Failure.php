<?php

namespace MercadoPago\Core\Controller\Basic;

use Magento\Catalog\Controller\Product\View\ViewInterface;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use MercadoPago\Core\Helper\ConfigData;

class Failure extends Action implements ViewInterface
{
    /**
     * @var Context
     */
    protected $_context;

    /**
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * Failure constructor.
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(Context $context, ScopeConfigInterface $scopeConfig)
    {
        $this->_context = $context;
        $this->_scopeConfig = $scopeConfig;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\App\Response\RedirectInterface|ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $redirect = $this->_context->getRedirect();
        $redirect->setUrl($this->_url->getUrl($this->_scopeConfig->getValue(ConfigData::PATH_BASIC_URL_FAILURE)));
        return $redirect;
    }
}
