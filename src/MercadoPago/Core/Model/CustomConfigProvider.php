<?php

namespace MercadoPago\Core\Model;

use Magento\Payment\Helper\Data as PaymentHelper;
use MercadoPago\Core\Lib\RestClient;
use MercadoPago\Core\Helper\ConfigData;
use Magento\Store\Model\ScopeInterface;
use Magento\Checkout\Model\ConfigProviderInterface;

/**
 * Return configs to Standard Method
 * Class StandardConfigProvider
 *
 * @package MercadoPago\Core\Model
 */
class CustomConfigProvider implements ConfigProviderInterface
{
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
    protected $_productMetadata;

    /**
     * @param PaymentHelper $paymentHelper
     */
    public function __construct(
        PaymentHelper $paymentHelper,
        \MercadoPago\Core\Helper\Data $coreHelper,
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\View\Asset\Repository $assetRepo,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\App\ProductMetadataInterface $productMetadata
    )
    {
        $this->_context = $context;
        $this->_request = $context->getRequest();
        $this->_assetRepo = $assetRepo;
        $this->_coreHelper = $coreHelper;
        $this->_scopeConfig = $scopeConfig;
        $this->_storeManager = $storeManager;
        $this->methodInstance = $paymentHelper->getMethodInstance($this->methodCode);
        $this->_checkoutSession = $checkoutSession;
        $this->_productMetaData = $productMetadata;
    }

    /**
     * Gather information to be sent to javascript method renderer
     *
     * @return array
     */
    public function getConfig()
    {
        if (!$this->methodInstance->isAvailable()) {
            return [];
        }

        $country = strtoupper($this->_scopeConfig->getValue(
            \MercadoPago\Core\Helper\ConfigData::PATH_SITE_ID,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        ));

        $walletButtonLink = $this->_coreHelper->getWalletButtonLink($country);

        $data = [
            'payment' => [
                $this->methodCode => [
                    'bannerUrl' => $this->_scopeConfig->getValue(
                        ConfigData::PATH_CUSTOM_BANNER, ScopeInterface::SCOPE_STORE
                    ),
                    'public_key' => $this->_scopeConfig->getValue(
                        ConfigData::PATH_PUBLIC_KEY, ScopeInterface::SCOPE_STORE
                    ),
                    'logEnabled' => $this->_scopeConfig->getValue(
                        ConfigData::PATH_ADVANCED_LOG, ScopeInterface::SCOPE_STORE
                    ),
                    'discount_coupon' => $this->_scopeConfig->isSetFlag(
                        ConfigData::PATH_CUSTOM_COUPON, ScopeInterface::SCOPE_STORE
                    ),
                    'mp_gateway_mode' => $this->_scopeConfig->getValue(
                        ConfigData::PATH_CUSTOM_GATEWAY_MODE, ScopeInterface::SCOPE_STORE
                    ),
                    'mp_wallet_button' => $this->_scopeConfig->getValue(
                        ConfigData::PATH_CUSTOM_WALLET_BUTTON, ScopeInterface::SCOPE_STORE
                    ),
                    'country' => $country,
                    'route' => $this->_request->getRouteName(),
                    'logoUrl' => $this->_assetRepo->getUrl("MercadoPago_Core::images/mp_logo.png"),
                    'minilogo' => $this->_assetRepo->getUrl("MercadoPago_Core::images/minilogo.png"),
                    'gray_minilogo' => $this->_assetRepo->getUrl("MercadoPago_Core::images/gray_minilogo.png"),
                    'base_url' => $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_LINK),
                    'customer' => $this->methodInstance->getCustomerAndCards(),
                    'grand_total' => $this->_checkoutSession->getQuote()->getGrandTotal(),
                    'success_url' => $this->methodInstance->getConfigData('order_place_redirect_url'),
                    'loading_gif' => $this->_assetRepo->getUrl('MercadoPago_Core::images/loading.gif'),
                    'text-choice' => __('Select'),
                    'text-currency' => __('$'),
                    'default-issuer' => __('Default issuer'),
                    'module_version' => $this->_coreHelper->getModuleVersion(),
                    'platform_version' => $this->_productMetaData->getVersion(),
                    'text-installment' => __('Enter the card number'),
                    'wallet_button_link' => $walletButtonLink,
                    'payment_methods' => $this->getPaymentMethods(),
                ],
            ],
        ];

        return $data;
    }

    /**
     * Get payment methods to show on checkout
     *
     * @return array
     */
    public function getPaymentMethods()
    {
        $accessToken = $this->_scopeConfig->getValue(ConfigData::PATH_ACCESS_TOKEN, ScopeInterface::SCOPE_WEBSITE);

        try {

            $cards = array();
            $paymentMethods = RestClient::get("/v1/payment_methods", null, ["Authorization: Bearer " . $accessToken]);
            $response = $paymentMethods['response'];

            foreach ($response as $card) {
                if ($card['payment_type_id'] == 'credit_card') {
                    $cards[] = $card;
                } elseif ($card['payment_type_id'] == 'debit_card' || $card['payment_type_id'] == 'prepaid_card') {
                    $cards[] = $card;
                }
            }

            return $cards;

        } catch (\Exception $e) {
            $this->_coreHelper->log(
                "[Custom config] getPaymentMethods:: An error occurred at the time of obtaining payment methods: " . $e
            );
        }
    }
}
