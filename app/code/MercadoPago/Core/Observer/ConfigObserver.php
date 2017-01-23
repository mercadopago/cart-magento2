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
    protected $scopeConfig;

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
        \Magento\Backend\Block\Store\Switcher $switcher
    )
    {
        $this->scopeConfig = $scopeConfig;
        $this->configResource = $configResource;
        $this->coreHelper = $coreHelper;
        $this->_switcher = $switcher;
    }

    /**
     * Updates configuration values based every time MercadoPago configuration section is saved
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

        $this->checkBanner('mercadopago_custom');
        $this->checkBanner('mercadopago_customticket');
        $this->checkBanner('mercadopago_standard');

    }

    /**
     * Disables custom checkout if selected country is not available
     */
    public function availableCheckout()
    {
        $country = $this->scopeConfig->getValue('payment/mercadopago/country');

        if (!in_array($country, $this->available_transparent_credit_cart)) {
            $this->_saveWebsiteConfig('payment/mercadopago_custom/active', 0);
        }

        if (!in_array($country, $this->available_transparent_ticket)) {
            $this->_saveWebsiteConfig('payment/mercadopago_customticket/active', 0);
        }
    }

    /**
     * Check if banner checkout img needs to be updated based on selected country
     * @param $typeCheckout
     */
    public function checkBanner($typeCheckout)
    {
        //get country
        $country = $this->scopeConfig->getValue('payment/mercadopago/country');
        if (!isset($this->banners[$typeCheckout][$country])) {
            return;
        }
        $defaultBanner = $this->banners[$typeCheckout][$country];

        $currentBanner = $this->scopeConfig->getValue('payment/' . $typeCheckout . '/banner_checkout');

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
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function setSponsor()
    {
        $this->coreHelper->log("Sponsor_id: " . $this->scopeConfig->getValue('payment/mercadopago/sponsor_id'), self::LOG_NAME);

        $sponsorId = "";
        $this->coreHelper->log("Valid user test", self::LOG_NAME);

        $accessToken = $this->scopeConfig->getValue(\MercadoPago\Core\Helper\Data::XML_PATH_ACCESS_TOKEN);
        $this->coreHelper->log("Get access_token: " . $accessToken, self::LOG_NAME);

        if (!$accessToken) {
            return;
        }

        $mp = $this->coreHelper->getApiInstance($accessToken);
        $user = $mp->get("/users/me");
        $this->coreHelper->log("API Users response", self::LOG_NAME, $user);

        if ($user['status'] == 200 && !in_array("test_user", $user['response']['tags'])) {

            $sponsors = [
                'MLA' => 186172525,
                'MLB' => 186175129,
                'MLM' => 186175064,
                'MCO' => 206959966,
                'MLC' => 206959756,
                'MLV' => 206960619,
                'MPE' => 217178514,
            ];
            $countryCode = $user['response']['site_id'];

            if (isset($sponsors[$countryCode])) {
                $sponsorId = $sponsors[$countryCode];
            } else {
                $sponsorId = '';
            }

            $this->coreHelper->log("Sponsor id set", self::LOG_NAME, $sponsorId);
        }

        $this->_saveWebsiteConfig('payment/mercadopago/sponsor_id', $sponsorId);
        $this->coreHelper->log("Sponsor saved", self::LOG_NAME, $sponsorId);
    }

    /**
     * Validate current accessToken
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function validateAccessToken()
    {
        $accessToken = $this->scopeConfig->getValue(\MercadoPago\Core\Helper\Data::XML_PATH_ACCESS_TOKEN);
        if (!empty($accessToken)) {
            if (!$this->coreHelper->isValidAccessToken($accessToken)) {
                throw new \Magento\Framework\Exception\LocalizedException(__('Mercado Pago - Custom Checkout: Invalid access token'));
            }
        }
    }

    /**
     * Validate current clientId and clientSecret
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function validateClientCredentials()
    {
        $clientId = $this->scopeConfig->getValue(\MercadoPago\Core\Helper\Data::XML_PATH_CLIENT_ID);
        $clientSecret = $this->scopeConfig->getValue(\MercadoPago\Core\Helper\Data::XML_PATH_CLIENT_SECRET);
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

    protected function validateRefundData ()
    {
        $refundAvailable = $this->coreHelper->isRefundAvailable();

        if ($refundAvailable) {
            $maxDays = $this->coreHelper->getMaximumDaysRefund();
            $maxRefunds = $this->coreHelper->getMaximumPartialRefunds();
            if (!($maxDays !== 0) && !($maxRefunds !== 0)) {
                throw new \Magento\Framework\Exception\LocalizedException(__('Mercado Pago - If refunds are available, you must set \'Maximum amount of partial refunds on the same order\' and \'Maximum amount of days until refund is not accepted\''));
            }
        }
    }
}
