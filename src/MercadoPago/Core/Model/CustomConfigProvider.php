<?php

namespace MercadoPago\Core\Model;

use Exception;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Asset\Repository;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Payment\Model\MethodInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use MercadoPago\Core\Helper\ConfigData;
use MercadoPago\Core\Helper\Data;
use MercadoPago\Core\Lib\RestClient;

/**
 * Return configs to Standard Method
 * Class StandardConfigProvider
 *
 * @package MercadoPago\Core\Model
 */
class CustomConfigProvider implements ConfigProviderInterface
{
    /**
     * @var MethodInterface
     */
    protected $methodInstance;

    /**
     * @var string
     */
    protected $methodCode = Custom\Payment::CODE;

    /**
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var Session
     */
    protected $_checkoutSession;

    /**
     * Store manager
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
     * @var Context
     */
    protected $_context;

    /**
     * @var ProductMetadataInterface
     */
    protected $_productMetaData;

    /**
     * @var Data
     */
    protected $_coreHelper;

    /**
     * CustomConfigProvider constructor.
     *
     * @param PaymentHelper $paymentHelper
     * @param Data $coreHelper
     * @param Context $context
     * @param Session $checkoutSession
     * @param Repository $assetRepo
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $scopeConfig
     * @param ProductMetadataInterface $productMetadata
     * @throws LocalizedException
     */
    public function __construct(
        PaymentHelper            $paymentHelper,
        Data                     $coreHelper,
        Context                  $context,
        Session                  $checkoutSession,
        Repository               $assetRepo,
        StoreManagerInterface    $storeManager,
        ScopeConfigInterface     $scopeConfig,
        ProductMetadataInterface $productMetadata
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
     * @return array|\array[][]
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getConfig()
    {
        if (!$this->methodInstance->isAvailable()) {
            return [];
        }

        $country = strtoupper($this->_scopeConfig->getValue(
            ConfigData::PATH_SITE_ID,
            ScopeInterface::SCOPE_STORE
        ));

        $walletButtonLink = $this->_coreHelper->getWalletButtonLink($country);

        return [
            'payment' => [
                $this->methodCode => [
                    'bannerUrl' => $this->_scopeConfig->getValue(
                        ConfigData::PATH_CUSTOM_BANNER,
                        ScopeInterface::SCOPE_STORE
                    ),
                    'public_key' => $this->_scopeConfig->getValue(
                        ConfigData::PATH_PUBLIC_KEY,
                        ScopeInterface::SCOPE_STORE
                    ),
                    'logEnabled' => $this->_scopeConfig->getValue(
                        ConfigData::PATH_ADVANCED_LOG,
                        ScopeInterface::SCOPE_STORE
                    ),
                    'mp_gateway_mode' => $this->_scopeConfig->getValue(
                        ConfigData::PATH_CUSTOM_GATEWAY_MODE,
                        ScopeInterface::SCOPE_STORE
                    ),
                    'mp_wallet_button' => $this->_scopeConfig->getValue(
                        ConfigData::PATH_CUSTOM_WALLET_BUTTON,
                        ScopeInterface::SCOPE_STORE
                    ),
                    'country' => $country,
                    'route' => $this->_request->getRouteName(),
                    'logoUrl' => $this->_assetRepo->getUrl("MercadoPago_Core::images/mp_logo.png"),
                    'minilogo' => $this->_assetRepo->getUrl("MercadoPago_Core::images/minilogo.png"),
                    'gray_minilogo' => $this->_assetRepo->getUrl("MercadoPago_Core::images/gray_minilogo.png"),
                    'base_url' => $this->_storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_LINK),
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
                    'creditcard_mini' => $this->_assetRepo->getUrl("MercadoPago_Core::images/creditcard-mini.png"),
                    'fingerprint_link' => $this->_coreHelper->getFingerPrintLink($country),
                ],
            ],
        ];
    }

    /**
     * Get payment methods to show on checkout
     *
     * @return array|void
     */
    public function getPaymentMethods()
    {
        $accessToken = $this->_scopeConfig->getValue(ConfigData::PATH_ACCESS_TOKEN, ScopeInterface::SCOPE_WEBSITE);

        try {
            $cards = [];
            $paymentMethods = $this->_coreHelper->getMercadoPagoPaymentMethods($accessToken);
            $response = $paymentMethods['response'];

            foreach ($response as $card) {
                if ($card['payment_type_id'] == 'credit_card') {
                    $cards[] = $card;
                } elseif ($card['payment_type_id'] == 'debit_card' || $card['payment_type_id'] == 'prepaid_card') {
                    $cards[] = $card;
                }
            }

            return $cards;
        } catch (Exception $e) {
            $this->_coreHelper->log(
                "[Custom config] getPaymentMethods:: An error occurred at the time of obtaining payment methods: " . $e
            );
        }
    }
}
