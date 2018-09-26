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
class CustomConfigProvider implements ConfigProviderInterface {
  
    /**
     * @var \Magento\Payment\Model\MethodInterface
     */
    protected $methodInstance;

    /**
     * @var string
     */
    protected $methodCode = Custom\Payment::CODE;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

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
     * @var \Magento\Framework\App\Action\Context
     */
    protected $_context;

    /**
     * @var \Magento\Framework\App\ProductMetadataInterface
     */
    protected $_productMetaData;

    protected $_composerInformation;

    protected $_coreHelper;

    /**
     * @param PaymentHelper $paymentHelper
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        PaymentHelper $paymentHelper,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\View\Asset\Repository $assetRepo,
        \Magento\Framework\App\ProductMetadataInterface $productMetadata,
        \MercadoPago\Core\Helper\Data $coreHelper
    )
    {
        $this->_request = $context->getRequest();
        $this->methodInstance = $paymentHelper->getMethodInstance($this->methodCode);
        $this->_scopeConfig = $scopeConfig;
        $this->_checkoutSession = $checkoutSession;
        $this->_storeManager = $storeManager;
        $this->_assetRepo = $assetRepo;
        $this->_context = $context;
        $this->_productMetaData = $productMetadata;
        $this->_coreHelper = $coreHelper;
    }


    /**
     * Gather information to be sent to javascript method renderer
     *
     * @return array
     */
    public function getConfig() {
          
        if (!$this->methodInstance->isAvailable()) {
                    
            return [];
        }
      
          $data = [
            'payment' => [
              $this->methodCode => [
                'bannerUrl'        => $this->_scopeConfig->getValue(\MercadoPago\Core\Helper\ConfigData::PATH_CUSTOM_BANNER, \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                'country'          => strtoupper($this->_scopeConfig->getValue(\MercadoPago\Core\Helper\ConfigData::PATH_SITE_ID, \Magento\Store\Model\ScopeInterface::SCOPE_STORE)),
                'public_key'       => $this->_scopeConfig->getValue(\MercadoPago\Core\Helper\ConfigData::PATH_PUBLIC_KEY, \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                'logEnabled'       => $this->_scopeConfig->getValue(\MercadoPago\Core\Helper\ConfigData::PATH_ADVANCED_LOG, \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                'discount_coupon'  => $this->_scopeConfig->isSetFlag(\MercadoPago\Core\Helper\ConfigData::PATH_CUSTOM_COUPON, \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                'grand_total'      => $this->_checkoutSession->getQuote()->getGrandTotal(),
                'base_url'         => $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_LINK),
                'success_url'      => $this->methodInstance->getConfigData('order_place_redirect_url'),
                'route'            => $this->_request->getRouteName(),
                'customer'         => $this->methodInstance->getCustomerAndCards(),
                'loading_gif'      => $this->_assetRepo->getUrl('MercadoPago_Core::images/loading.gif'),
                'text-currency'    => __('$'),
                'text-choice'      => __('Select'),
                'default-issuer'   => __('Default issuer'),
                'text-installment' => __('Enter the card number'),
                'logoUrl'          => $this->_assetRepo->getUrl("MercadoPago_Core::images/mp_logo.png"),
                'platform_version' => $this->_productMetaData->getVersion(),
                'module_version'   => $this->_coreHelper->getModuleVersion()
                ],
            ],
        ];
      
        return $data;
    }

}