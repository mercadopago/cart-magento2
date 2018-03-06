<?php

namespace MercadoPago\Core\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Payment\Helper\Data as PaymentHelper;

/**
 * Return configs to Standard Method
 *
 * Class StandardConfigProvider
 *
 * @package MercadoPago\Core\Model
 */
class CustomTicketConfigProvider
    implements ConfigProviderInterface
{
    /**
     * @var \Magento\Payment\Model\MethodInterface
     */
    protected $methodInstance;

    /**
     * @var string
     */
    protected $methodCode = CustomTicket\Payment::CODE;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * Store manager
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $_request;

    /**
     * @var \Magento\Framework\View\Asset\Repository
     */
    protected $_assetRepo;
    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $_urlBuilder;
    protected $_coreHelper;
    protected $_productMetaData;

    /**
     * @param PaymentHelper $paymentHelper
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        PaymentHelper $paymentHelper,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\View\Asset\Repository $assetRepo,
        \MercadoPago\Core\Helper\Data $coreHelper,
        \Magento\Framework\App\ProductMetadataInterface $productMetadata
    )
    {
        $this->_request = $context->getRequest();
        $this->methodInstance = $paymentHelper->getMethodInstance($this->methodCode);
        $this->_checkoutSession = $checkoutSession;
        $this->_scopeConfig = $scopeConfig;
        $this->_urlBuilder = $context->getUrl();
        $this->_storeManager = $storeManager;
        $this->_assetRepo = $assetRepo;
        $this->_coreHelper = $coreHelper;
        $this->_productMetaData = $productMetadata;
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return $this->methodInstance->isAvailable() ? [
            'payment' => [
                $this->methodCode => [
                    'bannerUrl'       => $this->methodInstance->getConfigData('banner_checkout'),
                    'options'         => $this->methodInstance->getTicketsOptions(),
                    'country'         => strtoupper($this->_scopeConfig->getValue('payment/mercadopago/country', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)),
                    'grand_total'     => $this->_checkoutSession->getQuote()->getGrandTotal(),
                    'success_url'     => $this->methodInstance->getConfigData('order_place_redirect_url'),
                    'route'           => $this->_request->getRouteName(),
                    'base_url'        => $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_LINK),
                    'discount_coupon' => $this->_scopeConfig->getValue('payment/mercadopago_customticket/coupon_mercadopago', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                    'loading_gif'     => $this->_assetRepo->getUrl('MercadoPago_Core::images/loading.gif'),
                    'logEnabled'      => $this->_scopeConfig->getValue('payment/mercadopago/logs', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                    'logoUrl'         => $this->_assetRepo->getUrl("MercadoPago_Core::images/mp_logo.png"),
                    'analytics_key'   => $this->_coreHelper->getClientIdFromAccessToken($this->_scopeConfig->getValue(\MercadoPago\Core\Helper\Data::XML_PATH_ACCESS_TOKEN, \Magento\Store\Model\ScopeInterface::SCOPE_STORE)),
                    'platform_version' => $this->_productMetaData->getVersion(),
                    'module_version'   => $this->_coreHelper->getModuleVersion()
                ],
            ],
        ] : [];
    }



}