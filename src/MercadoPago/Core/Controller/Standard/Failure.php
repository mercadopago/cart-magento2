<?php
namespace MercadoPago\Core\Controller\Standard;

/**
 * Class Failure
 *
 * @package MercadoPago\Core\Controller\Standard
 */
class Failure
    extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;
    protected $_checkoutSession;
    protected $_orderFactory;
    protected $_helperData;
    protected $_catalogSession;

    /**
     * @param \Magento\Framework\App\Action\Context              $context
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \MercadoPago\Core\Helper\Data $helperData,
        \Magento\Catalog\Model\Session $catalogSession
    )
    {

        $this->_scopeConfig = $scopeConfig;
        $this->_checkoutSession = $checkoutSession;
        $this->_orderFactory = $orderFactory;
        $this->_helperData = $helperData;
        $this->_catalogSession = $catalogSession;

        parent::__construct(
            $context
        );

    }

    /**
     * @return \Magento\Sales\Model\Order
     */
    protected function _getOrder()
    {
        $orderIncrementId = $this->_checkoutSession->getLastRealOrderId();
        $order = $this->_orderFactory->create()->loadByIncrementId($orderIncrementId);

        return $order;
    }

    /**
     * Execute Failure action
     */
    public function execute()
    {
        if (!$this->_scopeConfig->getValue(\MercadoPago\Core\Helper\Data::XML_PATH_USE_SUCCESSPAGE_MP, \Magento\Store\Model\ScopeInterface::SCOPE_STORE)){
            $this->_view->loadLayout(['default', 'checkout_onepage_failure']);

        } else {
            //set data for mp analytics
            $this->_catalogSession->setPaymentData($this->_helperData->getAnalyticsData($this->_getOrder()));

            $typeCheckout = $this->_scopeConfig->getValue('payment/mercadopago_standard/type_checkout', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

            $this->_view->loadLayout(['default', 'mercadopago_standard_failure_' . $typeCheckout]);

        }
        $this->_view->renderLayout();
    }

}