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
 * Return configs to Standard Method
 *
 * Class StandardConfigProvider
 *
 * @package MercadoPago\Core\Model
 */
class CustomBankTransferConfigProvider implements ConfigProviderInterface
{
    /**
     * @var MethodInterface
     */
    protected $methodInstance;

    /**
     * @var string
     */
    protected $methodCode = CustomBankTransfer\Payment::CODE;

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
     * @param PaymentHelper $paymentHelper
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
     */
    public function getConfig()
    {
        if (!$this->methodInstance->isAvailable()) {
            return [];
        }

        $paymentMethods = $this->methodInstance->getPaymentOptions();
        if (empty($paymentMethods)) {
            $this->_coreHelper->log("CustomTicketConfigProvider::getConfig - You have excluded all payment methods, the customer can not make the payment.");
        }

        $identificationTypes = $this->methodInstance->getIdentifcationTypes();

        $country = strtoupper($this->_scopeConfig->getValue(ConfigData::PATH_SITE_ID, ScopeInterface::SCOPE_STORE));

        $data = [
            'payment' => [
                $this->methodCode => [
                    'country' => $country,
                    'bannerUrl' => $this->_scopeConfig->getValue(ConfigData::PATH_CUSTOM_BANK_TRANSFER_BANNER, ScopeInterface::SCOPE_STORE),
                    'payment_method_options' => $paymentMethods,
                    'identification_types' => $identificationTypes,
                    'success_url' => $this->methodInstance->getConfigData('order_place_redirect_url'),
                    'route' => $this->_request->getRouteName(),
                    'base_url' => $this->_storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_LINK),
                    'loading_gif' => $this->_assetRepo->getUrl('MercadoPago_Core::images/loading.gif'),
                    'logoUrl' => $this->_assetRepo->getUrl("MercadoPago_Core::images/mp_logo.png"),
                    'platform_version' => $this->_productMetaData->getVersion(),
                    'module_version' => $this->_coreHelper->getModuleVersion(),
                    'banktransfer_mini' => $this->_assetRepo->getUrl("MercadoPago_Core::images/ticket-mini.png"),
                    'fingerprint_link' => $this->_coreHelper->getFingerPrintLink($country),
                ]
            ]
        ];

        return $data;
    }
}
