<?php

namespace MercadoPago\Core\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Asset\Repository;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Payment\Model\MethodInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use MercadoPago\Core\Helper\ConfigData;
use MercadoPago\Core\Helper\Data;

/**
 * Class CustomBankTransferConfigProvider
 */
class CustomPixConfigProvider implements ConfigProviderInterface
{
    /**
     * @var MethodInterface
     */
    protected $methodInstance;

    /**
     * @var string
     */
    protected $methodCode = CustomPix\Payment::CODE;

    /**
     * @var Session
     */
    protected $_checkoutSession;

    /**
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * Store manager
     *
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var RequestInterface
     */
    protected $_request;

    /**
     * @var Repository
     */
    protected $_assetRepo;

    /**
     * @var UrlInterface
     */
    protected $_urlBuilder;
    protected $_coreHelper;
    protected $_productMetaData;

    /**
     * CustomBankTransferConfigProvider constructor.
     * @param Context $context
     * @param PaymentHelper $paymentHelper
     * @param Session $checkoutSession
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param Repository $assetRepo
     * @param Data $coreHelper
     * @param ProductMetadataInterface $productMetadata
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function __construct(
        Context $context,
        PaymentHelper $paymentHelper,
        Session $checkoutSession,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        Repository $assetRepo,
        Data $coreHelper,
        ProductMetadataInterface $productMetadata
    ) {
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
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getConfig()
    {
        if (!$this->methodInstance->isAvailable()) {
            return [];
        }

        $country = strtoupper($this->_scopeConfig->getValue(\MercadoPago\Core\Helper\ConfigData::PATH_SITE_ID, \Magento\Store\Model\ScopeInterface::SCOPE_STORE));

        return [
            'payment' => [
                $this->methodCode => [
                    'country' => $country,
                    'bannerUrl' => $this->_scopeConfig->getValue(ConfigData::PATH_CUSTOM_PIX_BANNER, ScopeInterface::SCOPE_STORE),
                    'success_url' => $this->methodInstance->getConfigData('order_place_redirect_url'),
                    'route' => $this->_request->getRouteName(),
                    'base_url' => $this->_storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_LINK),
                    'loading_gif' => $this->_assetRepo->getUrl('MercadoPago_Core::images/loading.gif'),
                    'logoUrl' => $this->_assetRepo->getUrl("MercadoPago_Core::images/mp_logo.png"),
                    'platform_version' => $this->_productMetaData->getVersion(),
                    'module_version' => $this->_coreHelper->getModuleVersion(),
                    'pix_logo' => $this->_assetRepo->getUrl("MercadoPago_Core::images/logo_pix.png"),
                    'pix_mini' => $this->_assetRepo->getUrl("MercadoPago_Core::images/pix-mini.png"),
                    'fingerprint_link' => $this->_coreHelper->getFingerPrintLink($country),
                ]
            ]
        ];
    }
}
