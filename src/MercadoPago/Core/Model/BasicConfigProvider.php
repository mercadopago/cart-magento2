<?php

namespace MercadoPago\Core\Model;

use Exception;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Checkout\Model\Session;
use Magento\Framework\View\Asset\Repository;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\App\Action\Context;
use MercadoPago\Core\Helper\ConfigData;
use MercadoPago\Core\Helper\Data;

/**
 * Class BasicConfigProvider
 * @package MercadoPago\Core\Model
 */
class BasicConfigProvider implements ConfigProviderInterface
{
    protected $methodCode = Basic\Payment::CODE;
    protected $_scopeConfig;
    protected $_methodInstance;
    protected $_checkoutSession;
    protected $_assetRepo;
    protected $_productMetaData;
    protected $_coreHelper;
    protected $_context;

    /**
     * BasicConfigProvider constructor.
     * @param Context $context
     * @param PaymentHelper $paymentHelper
     * @param ScopeConfigInterface $scopeConfig
     * @param Session $checkoutSession
     * @param Repository $assetRepo
     * @param ProductMetadataInterface $productMetadata
     * @param Data $coreHelper
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function __construct(
        Context $context,
        PaymentHelper $paymentHelper,
        ScopeConfigInterface $scopeConfig,
        Session $checkoutSession,
        Repository $assetRepo,
        ProductMetadataInterface $productMetadata,
        Data $coreHelper
    ) {
        $this->_methodInstance = $paymentHelper->getMethodInstance($this->methodCode);
        $this->_scopeConfig = $scopeConfig;
        $this->_checkoutSession = $checkoutSession;
        $this->_assetRepo = $assetRepo;
        $this->_productMetaData = $productMetadata;
        $this->_coreHelper = $coreHelper;
        $this->_context = $context;

    }

    /**
     * @return array
     */
    public function getConfig()
    {
        try{
            if (!$this->_methodInstance->isAvailable()) {
                return [];
            }

            $data = [
                'payment' => [
                    $this->methodCode => [
                        'active' => $this->_scopeConfig->getValue(ConfigData::PATH_BASIC_ACTIVE, ScopeInterface::SCOPE_STORE),
                        'actionUrl' => $this->_context->getUrl()->getUrl(Basic\Payment::ACTION_URL),
                        //'url_failure' => $this->_context->getUrl()->getUrl(Basic\Payment::FAILURE_URL),
                        'banner_checkout' => $this->_scopeConfig->getValue(ConfigData::PATH_BASIC_BANNER, ScopeInterface::SCOPE_STORE),
                        'type_checkout' => $this->_scopeConfig->getValue(ConfigData::PATH_BASIC_TYPE_CHECKOUT, ScopeInterface::SCOPE_STORE),
                        'logEnabled' => $this->_scopeConfig->getValue(ConfigData::PATH_ADVANCED_LOG, ScopeInterface::SCOPE_STORE),
                        'coupon_mercadopago' => $this->_scopeConfig->isSetFlag(ConfigData::PATH_BASIC_COUPON, ScopeInterface::SCOPE_STORE),
                        'sandbox_mode' => $this->_scopeConfig->getValue(ConfigData::PATH_BASIC_SANDBOX_MODE, ScopeInterface::SCOPE_STORE),
                        'max_installments' => $this->_scopeConfig->getValue(ConfigData::PATH_BASIC_MAX_INSTALLMENTS, ScopeInterface::SCOPE_STORE),
                        'auto_return' => $this->_scopeConfig->getValue(ConfigData::PATH_BASIC_AUTO_RETURN, ScopeInterface::SCOPE_STORE),
                        'exclude_payments' => $this->_scopeConfig->getValue(ConfigData::PATH_BASIC_EXCLUDE_PAYMENT_METHODS, ScopeInterface::SCOPE_STORE),
                        'order_status' => $this->_scopeConfig->getValue(ConfigData::PATH_BASIC_ORDER_STATUS, ScopeInterface::SCOPE_STORE),
                        'loading_gif' => $this->_assetRepo->getUrl('MercadoPago_Core::images/loading.gif'),
                        'logoUrl' => $this->_assetRepo->getUrl("MercadoPago_Core::images/mp_logo.png"),
                        'platform_version' => $this->_productMetaData->getVersion(),
                        'module_version' => $this->_coreHelper->getModuleVersion()
                    ],
                ],
            ];

            return $data;
        }catch (\Exception $e){
            $this->_coreHelper->log("BasicConfigProvider ERROR: ". $e->getMessage(), 'BasicConfigProvider');
            return [];
        }
    }
}
