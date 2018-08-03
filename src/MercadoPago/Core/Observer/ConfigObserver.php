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
        "mercadopago_custom"       => [
            "mla" => "http://imgmp.mlstatic.com/org-img/banners/ar/medios/online/468X60.jpg",
            "mlb" => "http://imgmp.mlstatic.com/org-img/MLB/MP/BANNERS/tipo2_468X60.jpg",
            "mco" => "https://a248.e.akamai.net/secure.mlstatic.com/components/resources/mp/css/assets/desktop-logo-mercadopago.png",
            "mlm" => "http://imgmp.mlstatic.com/org-img/banners/mx/medios/MLM_468X60.JPG",
            "mlc" => "https://secure.mlstatic.com/developers/site/cloud/banners/cl/468x60.gif",
            "mlv" => "https://imgmp.mlstatic.com/org-img/banners/ve/medios/468X60.jpg",
            "mpe" => "https://a248.e.akamai.net/secure.mlstatic.com/components/resources/mp/css/assets/desktop-logo-mercadopago.png",
        ],
        "mercadopago_customticket" => [
            "mla" => "https://a248.e.akamai.net/secure.mlstatic.com/components/resources/mp/css/assets/desktop-logo-mercadopago.png",
            "mlb" => "http://imgmp.mlstatic.com/org-img/MLB/MP/BANNERS/2014/230x60.png",
            "mco" => "https://a248.e.akamai.net/secure.mlstatic.com/components/resources/mp/css/assets/desktop-logo-mercadopago.png",
            "mlm" => "https://a248.e.akamai.net/secure.mlstatic.com/components/resources/mp/css/assets/desktop-logo-mercadopago.png",
            "mlc" => "https://secure.mlstatic.com/developers/site/cloud/banners/cl/468x60.gif",
            "mlv" => "https://imgmp.mlstatic.com/org-img/banners/ve/medios/468X60.jpg",
            "mpe" => "https://a248.e.akamai.net/secure.mlstatic.com/components/resources/mp/css/assets/desktop-logo-mercadopago.png",
        ],
        "mercadopago_standard"     => [
            "mla" => "http://imgmp.mlstatic.com/org-img/banners/ar/medios/online/468X60.jpg",
            "mlb" => "http://imgmp.mlstatic.com/org-img/MLB/MP/BANNERS/tipo2_468X60.jpg",
            "mco" => "https://a248.e.akamai.net/secure.mlstatic.com/components/resources/mp/css/assets/desktop-logo-mercadopago.png",
            "mlc" => "https://secure.mlstatic.com/developers/site/cloud/banners/cl/468x60.gif",
            "mlv" => "https://imgmp.mlstatic.com/org-img/banners/ve/medios/468X60.jpg",
            "mlm" => "http://imgmp.mlstatic.com/org-img/banners/mx/medios/MLM_468X60.JPG",
            "mpe" => "https://a248.e.akamai.net/secure.mlstatic.com/components/resources/mp/css/assets/desktop-logo-mercadopago.png",
        ]
    ];

    /**
     * Available countries to custom checkout
     *
     * @var array
     */
    private $available_transparent_credit_cart = ['mla', 'mlb', 'mlm', 'mlv', 'mlc', 'mco', 'mpe'];

    /**
     * Available countries to custom ticket
     *
     * @var array
     */
    private $available_transparent_ticket = ['mla', 'mlb', 'mlm'];

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
     * ConfigObserver constructor.
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \MercadoPago\Core\Helper\Data                      $coreHelper
     * @param \Magento\Config\Model\ResourceModel\Config         $configResource
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \MercadoPago\Core\Helper\Data $coreHelper,
        \Magento\Config\Model\ResourceModel\Config $configResource,
        \Magento\Backend\Block\Store\Switcher $switcher,
        \Magento\Framework\App\ProductMetadataInterface $productMetadata
    )
    {
        $this->_scopeConfig = $scopeConfig;
        $this->configResource = $configResource;
        $this->coreHelper = $coreHelper;
        $this->_switcher = $switcher;
        $this->_scopeCode = $this->_switcher->getWebsiteId();
        $this->_productMetaData = $productMetadata;
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

        $this->validateClientCredentials();

        $this->setSponsor();

        $this->availableCheckout();

        $this->validateRefundData();

        $this->checkAnalyticsData();

        $this->checkBanner('mercadopago_custom');
        $this->checkBanner('mercadopago_customticket');
        $this->checkBanner('mercadopago_standard');

    }

    /**
     * Disables custom checkout if selected country is not available
     */
    public function availableCheckout()
    {
        $country = $this->_scopeConfig->getValue(
            \MercadoPago\Core\Helper\Data::XML_PATH_COUNTRY,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $this->_scopeCode
        );

        if (!in_array($country, $this->available_transparent_credit_cart)) {
            $this->_saveWebsiteConfig(\MercadoPago\Core\Helper\Data::XML_PATH_MERCADOPAGO_CUSTOM_ACTIVE, 0);
        }

        if (!in_array($country, $this->available_transparent_ticket)) {
            $this->_saveWebsiteConfig(\MercadoPago\Core\Helper\Data::XML_PATH_MERCADOPAGO_TICKET_ACTIVE, 0);
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
        $country = $this->_scopeConfig->getValue(
            \MercadoPago\Core\Helper\Data::XML_PATH_COUNTRY,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $this->_scopeCode
        );

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
            }
        }
    }


    /**
     * Set configuration value sponsor_id based on current credentials
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function setSponsor()
    {
        $sponsorIdConfig = $this->_scopeConfig->getValue(
            \MercadoPago\Core\Helper\Data::XML_PATH_SPONSOR_ID,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $this->_scopeCode
        );

        $this->coreHelper->log("Sponsor_id: " . $sponsorIdConfig, self::LOG_NAME);

        $sponsorId = "";
        $this->coreHelper->log("Valid user test", self::LOG_NAME);

        $accessToken = $this->_scopeConfig->getValue(
            \MercadoPago\Core\Helper\Data::XML_PATH_ACCESS_TOKEN,
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

        if ($user['status'] == 200 && !in_array("test_user", $user['response']['tags'])) {

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

        $this->_saveWebsiteConfig(\MercadoPago\Core\Helper\Data::XML_PATH_SPONSOR_ID, $sponsorId);
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
            \MercadoPago\Core\Helper\Data::XML_PATH_ACCESS_TOKEN,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $this->_scopeCode
        );
        if (!empty($accessToken)) {
            if (!$this->coreHelper->isValidAccessToken($accessToken)) {
                throw new \Magento\Framework\Exception\LocalizedException(__('Mercado Pago - Custom Checkout: Invalid access token'));
            }
        }
    }

    /**
     * Validate current clientId and clientSecret
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function validateClientCredentials()
    {
        $clientId = $this->_scopeConfig->getValue(
            \MercadoPago\Core\Helper\Data::XML_PATH_CLIENT_ID,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $this->_scopeCode
        );
        $clientSecret = $this->_scopeConfig->getValue(
            \MercadoPago\Core\Helper\Data::XML_PATH_CLIENT_SECRET,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $this->_scopeCode
        );
        if (!empty($clientId) && !empty($clientSecret)) {
            if (!$this->coreHelper->isValidClientCredentials($clientId, $clientSecret)) {
                throw new \Magento\Framework\Exception\LocalizedException(__('Mercado Pago - Classic Checkout: Invalid client id or client secret'));
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

    protected function validateRefundData()
    {
        $refundAvailable = $this->_scopeConfig->getValue(
            \MercadoPago\Core\Helper\Data::XML_PATH_REFUND_AVAILABLE,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $this->_scopeCode
        );

        if ($refundAvailable) {
            $maxDays = $this->_scopeConfig->getValue(
                \MercadoPago\Core\Helper\Data::XML_PATH_MAXIMUM_DAYS_REFUND,
                \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
                $this->_scopeCode
            );
            $maxRefunds = $this->_scopeConfig->getValue(
                \MercadoPago\Core\Helper\Data::XML_PATH_MAXIMUM_PARTIAL_REFUNDS,
                \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
                $this->_scopeCode
            );
            if (($maxDays === 0) || ($maxRefunds === 0)) {
                throw new \Magento\Framework\Exception\LocalizedException(__('Mercado Pago - If refunds are available, you must set \'Maximum amount of partial refunds on the same order\' and \'Maximum amount of days until refund is not accepted\''));
            }
        }
    }

    protected function checkAnalyticsData()
    {
        $accessToken = $this->_scopeConfig->getValue(
            \MercadoPago\Core\Helper\Data::XML_PATH_ACCESS_TOKEN,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $this->_scopeCode
        );
        if (!$this->coreHelper->isValidAccessToken($accessToken)) {
            $clientId = $this->_scopeConfig->getValue(
                \MercadoPago\Core\Helper\Data::XML_PATH_CLIENT_ID,
                \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
                $this->_scopeCode
            );
            $clientSecret = $this->_scopeConfig->getValue(
                \MercadoPago\Core\Helper\Data::XML_PATH_CLIENT_SECRET,
                \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
                $this->_scopeCode
            );

            $this->sendAnalyticsData($this->coreHelper->getApiInstance($clientId, $clientSecret));

        } else {

            $this->sendAnalyticsData($this->coreHelper->getApiInstance($accessToken));

        }


    }

    protected function sendAnalyticsData($api)
    {
        $request = [
            "data"    => [
                "platform"         => "Magento",
                "platform_version" => $this->_productMetaData->getVersion(),
                "module_version"   => $this->coreHelper->getModuleVersion(),
                "code_version"     => phpversion()
            ],
        ];
        $standard = $this->_scopeConfig->getValue('payment/mercadopago_standard/active',
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $this->_scopeCode);
        $custom = $this->_scopeConfig->getValue('payment/mercadopago_custom/active',
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $this->_scopeCode);
        $customTicket = $this->_scopeConfig->getValue('payment/mercadopago_customticket/active',
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $this->_scopeCode);
        $mercadoEnvios = $this->_scopeConfig->getValue('carriers/mercadoenvios/active',
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $this->_scopeCode);
        $twoCards = $this->_scopeConfig->getValue('payment/mercadopago_custom/allow_2_cards',
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $this->_scopeCode);
        $customCoupon = $this->_scopeConfig->getValue('payment/mercadopago_custom/coupon_mercadopago',
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $this->_scopeCode);
        $customTicketCoupon = $this->_scopeConfig->getValue('payment/mercadopago_customticket/coupon_mercadopago',
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $this->_scopeCode);

        $request['data']['two_cards'] = $twoCards == 1 ? 'true' : 'false';
        $request['data']['checkout_basic'] = $standard == 1 ? 'true' : 'false';
        $request['data']['checkout_custom_credit_card'] = $custom == 1 ? 'true' : 'false';
        $request['data']['checkout_custom_ticket'] = $customTicket == 1 ? 'true' : 'false';
        $request['data']['mercado_envios'] = $mercadoEnvios == 1 ? 'true' : 'false';
        $request['data']['two_cards'] = $twoCards == 1 ? 'true' : 'false';
        $request['data']['checkout_custom_credit_card_coupon'] = $customCoupon == 1 ? 'true' : 'false';
        $request['data']['checkout_custom_ticket_coupon'] = $customTicketCoupon == 1 ? 'true' : 'false';

        $this->coreHelper->log("Analytics settings request sent /modules/tracking/settings", self::LOG_NAME, $request);
        $response = $api->post("/modules/tracking/settings", $request['data']);
        $this->coreHelper->log("Analytics settings response", self::LOG_NAME, $response);

    }
}
