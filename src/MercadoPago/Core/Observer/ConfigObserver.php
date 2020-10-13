<?php

namespace MercadoPago\Core\Observer;

use Magento\Framework\Event\ObserverInterface;

/**
 * Class ConfigObserver
 *
 * @package MercadoPago\Core\Observer
 */
class ConfigObserver
    implements ObserverInterface
{
    /**
     * url banners grouped by country
     *
     * @var array
     */
    private $banners = [
        "mercadopago_custom" => [
            "MLA" => "http://imgmp.mlstatic.com/org-img/banners/ar/medios/online/468X60.jpg",
            "MLB" => "http://imgmp.mlstatic.com/org-img/MLB/MP/BANNERS/tipo2_468X60.jpg",
            "MCO" => "https://a248.e.akamai.net/secure.mlstatic.com/components/resources/mp/css/assets/desktop-logo-mercadopago.png",
            "MLM" => "http://imgmp.mlstatic.com/org-img/banners/mx/medios/MLM_468X60.JPG",
            "MLC" => "https://secure.mlstatic.com/developers/site/cloud/banners/cl/468x60.gif",
            "MLV" => "https://imgmp.mlstatic.com/org-img/banners/ve/medios/468X60.jpg",
            "MPE" => "https://a248.e.akamai.net/secure.mlstatic.com/components/resources/mp/css/assets/desktop-logo-mercadopago.png",
        ],
        "mercadopago_customticket" => [
            "MLA" => "",
            "MLB" => "",
            "MCO" => "",
            "MLM" => "",
            "MLC" => "",
            "MLV" => "",
            "MPE" => ""
        ]
    ];

    /**
     * Available countries to custom checkout
     *
     * @var array
     */
    private $available_transparent_credit_cart = ['MLA', 'MLB', 'MLM', 'MLV', 'MLC', 'MCO', 'MPE'];

    /**
     * Available countries to custom ticket
     *
     * @var array
     */
    private $available_transparent_ticket = ['MLA', 'MLB', 'MLM', 'MPE'];

    /**
     *
     */
    const LOG_NAME = 'mercadopago';

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var \MercadoPago\Core\Helper\
     */
    protected $coreHelper;

    /**
     * Config configResource
     *
     * @var $configResource
     */
    protected $configResource;
    protected $_switcher;
    protected $_scopeCode;
    protected $_productMetaData;

    /**
     * @var \Magento\Framework\App\Cache\TypeListInterface
     */
    protected $cacheTypeList;


    protected $country;

    /**
     * ConfigObserver constructor.
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \MercadoPago\Core\Helper\Data $coreHelper
     * @param \Magento\Config\Model\ResourceModel\Config $configResource
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \MercadoPago\Core\Helper\Data $coreHelper,
        \Magento\Config\Model\ResourceModel\Config $configResource,
        \Magento\Backend\Block\Store\Switcher $switcher,
        \Magento\Framework\App\ProductMetadataInterface $productMetadata,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
    )
    {
        $this->_scopeConfig = $scopeConfig;
        $this->configResource = $configResource;
        $this->coreHelper = $coreHelper;
        $this->_switcher = $switcher;
        $this->_scopeCode = $this->_switcher->getWebsiteId();
        $this->_productMetaData = $productMetadata;
        $this->cacheTypeList = $cacheTypeList;
        $this->country = "";
    }

    /**
     * Updates configuration values based every time MercadoPago configuration section is saved
     *
     * @param \Magento\Framework\Event\Observer $observer
     *
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {

        $this->validateAccessToken();

        $this->setUserInfo();

        $this->availableCheckout();

        $this->checkAnalyticsData();

        $this->checkBanner('mercadopago_custom');

        $this->checkBanner('mercadopago_customticket');

    }

    /**
     * Disables custom checkout if selected country is not available
     */
    public function availableCheckout()
    {
        $country = $this->country;

        if ($country == "") {
            $country = $this->_scopeConfig->getValue(
                \MercadoPago\Core\Helper\ConfigData::PATH_SITE_ID,
                \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
                $this->_scopeCode
            );
        }

        if (!in_array(strtoupper($country), $this->available_transparent_credit_cart)) {
            $this->_saveWebsiteConfig(\MercadoPago\Core\Helper\ConfigData::PATH_CUSTOM_ACTIVE, 0);
        }

        if (!in_array(strtoupper($country), $this->available_transparent_ticket)) {
            $this->_saveWebsiteConfig(\MercadoPago\Core\Helper\ConfigData::PATH_CUSTOM_TICKET_ACTIVE, 0);
        }
    }

    /**
     * Check if banner checkout img needs to be updated based on selected country
     *
     * @param $typeCheckout
     */
    public function checkBanner($typeCheckout)
    {
        //get country
        $country = $this->country;

        if ($country == "") {
            $country = $this->_scopeConfig->getValue(
                \MercadoPago\Core\Helper\ConfigData::PATH_SITE_ID,
                \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
                $this->_scopeCode
            );
        }

        if (!isset($this->banners[$typeCheckout][$country])) {
            return;
        }
        $defaultBanner = $this->banners[$typeCheckout][$country];

        $currentBanner = $this->_scopeConfig->getValue(
            'payment/' . $typeCheckout . '/banner_checkout',
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $this->_scopeCode
        );

        $this->coreHelper->log("Type Checkout Path: " . $typeCheckout, self::LOG_NAME);
        $this->coreHelper->log("Current Banner: " . $currentBanner, self::LOG_NAME);
        $this->coreHelper->log("Default Banner: " . $defaultBanner, self::LOG_NAME);

        if (in_array($currentBanner, $this->banners[$typeCheckout])) {
            $this->coreHelper->log("Banner default need update...", self::LOG_NAME);

            if ($defaultBanner != $currentBanner) {
                $this->_saveWebsiteConfig('payment/' . $typeCheckout . '/banner_checkout', $defaultBanner);
                $this->coreHelper->log('payment/' . $typeCheckout . '/banner_checkout setted ' . $defaultBanner, self::LOG_NAME);

                $this->cacheTypeList->cleanType(\Magento\Framework\App\Cache\Type\Config::TYPE_IDENTIFIER);
                $this->cacheTypeList->cleanType(\Magento\PageCache\Model\Cache\Type::TYPE_IDENTIFIER);
            }
        }
    }


    /**
     * Set configuration value sponsor_id based on current credentials
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function setUserInfo()
    {
        $sponsorIdConfig = $this->_scopeConfig->getValue(
            \MercadoPago\Core\Helper\ConfigData::PATH_SPONSOR_ID,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $this->_scopeCode
        );

        $this->coreHelper->log("Sponsor_id: " . $sponsorIdConfig, self::LOG_NAME);

        $sponsorId = "";
        $siteId = "not_defined";

        $this->coreHelper->log("Valid user test", self::LOG_NAME);

        $accessToken = $this->_scopeConfig->getValue(
            \MercadoPago\Core\Helper\ConfigData::PATH_ACCESS_TOKEN,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $this->_scopeCode
        );

        $this->coreHelper->log("Get access_token: " . $accessToken, self::LOG_NAME);

        if (!$accessToken) {
            return;
        }

        $mp = $this->coreHelper->getApiInstance($accessToken);
        $user = $mp->get("/users/me");
        $this->coreHelper->log("API Users response", self::LOG_NAME, $user);

        if ($user['status'] == 200) {

            $siteId = $user['response']['site_id'];

            if (!in_array("test_user", $user['response']['tags'])) {

                $sponsors = [
                    'MLA' => 222568987,
                    'MLB' => 222567845,
                    'MLM' => 222568246,
                    'MCO' => 222570694,
                    'MLC' => 222570571,
                    'MLV' => 222569730,
                    'MPE' => 222568315,
                    'MLU' => 247030424,
                ];
                $countryCode = $user['response']['site_id'];

                if (isset($sponsors[$countryCode])) {
                    $sponsorId = $sponsors[$countryCode];
                } else {
                    $sponsorId = '';
                }

                $this->coreHelper->log("Sponsor id set", self::LOG_NAME, $sponsorId);
            }
        }

        $this->_saveWebsiteConfig(\MercadoPago\Core\Helper\ConfigData::PATH_SPONSOR_ID, $sponsorId);
        $this->_saveWebsiteConfig(\MercadoPago\Core\Helper\ConfigData::PATH_SITE_ID, $siteId);

        $this->country = $siteId;
        $this->coreHelper->log("Site_id saved", self::LOG_NAME, $siteId);
        $this->coreHelper->log("Sponsor saved", self::LOG_NAME, $sponsorId);
    }

    /**
     * Validate current accessToken
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function validateAccessToken()
    {

        $accessToken = $this->_scopeConfig->getValue(
            \MercadoPago\Core\Helper\ConfigData::PATH_ACCESS_TOKEN,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $this->_scopeCode
        );
        if (!empty($accessToken)) {
            if (!$this->coreHelper->isValidAccessToken($accessToken)) {
                throw new \Magento\Framework\Exception\LocalizedException(__('Mercado Pago - Custom Checkout: Invalid access token'));
            }
        }
    }

    protected function _saveWebsiteConfig($path, $value)
    {
        if ($this->_switcher->getWebsiteId() == 0) {
            $this->configResource->saveConfig($path, $value, 'default', 0);
        } else {
            $this->configResource->saveConfig($path, $value, 'websites', $this->_switcher->getWebsiteId());
        }

    }

    protected function checkAnalyticsData()
    {
        $accessToken = $this->_scopeConfig->getValue(
            \MercadoPago\Core\Helper\ConfigData::PATH_ACCESS_TOKEN,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $this->_scopeCode
        );

        $this->sendAnalyticsData($this->coreHelper->getApiInstance($accessToken));
    }

    protected function sendAnalyticsData($api)
    {
        $request = [
            "data" => [
                "platform" => "Magento",
                "platform_version" => $this->_productMetaData->getVersion(),
                "module_version" => $this->coreHelper->getModuleVersion(),
                "code_version" => phpversion()
            ],
        ];

        $custom = $this->_scopeConfig->getValue('payment/mercadopago_custom/active',
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $this->_scopeCode);
        $customTicket = $this->_scopeConfig->getValue('payment/mercadopago_customticket/active',
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $this->_scopeCode);
        $customCoupon = $this->_scopeConfig->getValue('payment/mercadopago_custom/coupon_mercadopago',
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $this->_scopeCode);
        $customTicketCoupon = $this->_scopeConfig->getValue('payment/mercadopago_customticket/coupon_mercadopago',
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $this->_scopeCode);

        $request['data']['checkout_custom_credit_card'] = $custom == 1 ? 'true' : 'false';
        $request['data']['checkout_custom_ticket'] = $customTicket == 1 ? 'true' : 'false';
        $request['data']['checkout_custom_credit_card_coupon'] = $customCoupon == 1 ? 'true' : 'false';
        $request['data']['checkout_custom_ticket_coupon'] = $customTicketCoupon == 1 ? 'true' : 'false';

        $this->coreHelper->log("Analytics settings request sent /modules/tracking/settings", self::LOG_NAME, $request);
        $response = $api->post("/modules/tracking/settings", $request['data']);
        $this->coreHelper->log("Analytics settings response", self::LOG_NAME, $response);

    }
}
