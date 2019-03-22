<?php

namespace MercadoPago\Core\Controller\Basic;

use Magento\Catalog\Controller\Product\View\ViewInterface;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use MercadoPago\Core\Helper\ConfigData;

class Failure extends Action implements ViewInterface
{
    /**
     * @var Context
     */
    protected $_context;

    /**
     * Failure constructor.
     * @param Context $context
     */
    public function __construct(Context $context)
    {
        parent::__construct($context);
        $this->_context = $context;
    }

    /**
     * @return \Magento\Framework\App\Response\RedirectInterface|ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $redirect = $this->_context->getRedirect();
        $redirect->setUrl($this->_url->getUrl(ConfigData::PATH_BASIC_URL_FAILURE));
        return $redirect;
    }
}
