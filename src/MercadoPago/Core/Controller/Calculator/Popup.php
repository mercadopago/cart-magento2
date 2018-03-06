<?php

namespace MercadoPago\Core\Controller\Calculator;

/**
 * Class Index action controller to calculator popUp
 *
 * @package Mercadopago\Core\Controller\Calculator
 */
class Popup
    extends \Magento\Framework\App\Action\Action
{

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $_pageFactory;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig

    )
    {
        $this->_pageFactory = $pageFactory;
        $this->_scopeConfig = $scopeConfig;
        parent::__construct($context);
    }

    /**
     * Execute action
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $this->_view->loadLayout(['default', 'mercadopago_calculator_popup']);
        return $this->_pageFactory->create();
    }

}