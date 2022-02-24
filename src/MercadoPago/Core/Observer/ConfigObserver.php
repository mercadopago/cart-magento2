<?php

namespace MercadoPago\Core\Observer;

use Magento\Backend\Block\Store\Switcher;
use Magento\Config\Model\ResourceModel\Config;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\ScopeInterface;
use MercadoPago\Core\Helper\ConfigData;
use MercadoPago\Core\Helper\Data;

/**
 * Class ConfigObserver
 *
 * @package MercadoPago\Core\Observer
 * 
 * @codeCoverageIgnore
 */
class ConfigObserver implements ObserverInterface
{
    /**
     * url banners grouped by country
     *
     * @var array
     */
    private $banners = [
        "mercadopago_custom" => [
            "MLA" => "https://imgmp.mlstatic.com/org-img/banners/ar/medios/online/468X60.jpg",
            "MLB" => "https://imgmp.mlstatic.com/org-img/MLB/MP/BANNERS/tipo2_468X60.jpg",
            "MCO" => "https://a248.e.akamai.net/secure.mlstatic.com/components/resources/mp/css/assets/desktop-logo-mercadopago.png",
            "MLM" => "https://imgmp.mlstatic.com/org-img/banners/mx/medios/MLM_468X60.JPG",
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
     *
     */
    const LOG_NAME = 'mercadopago';

    /**
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var
     */
    protected $coreHelper;

    /**
     * @var $configResource
     */
    protected $configResource;

    /**
     * @var Switcher
     */
    protected $_switcher;

    /**
     * @var int|null
     */
    protected $_scopeCode;

    /**
     * @var ProductMetadataInterface
     */
    protected $_productMetaData;

    /**
     * @var TypeListInterface
     */
    protected $cacheTypeList;

    protected $country;

    /**
     * ConfigObserver constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param Data $coreHelper
     * @param Config $configResource
     * @param Switcher $switcher
     * @param ProductMetadataInterface $productMetadata
     * @param TypeListInterface $cacheTypeList
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Data $coreHelper,
        Config $configResource,
        Switcher $switcher,
        ProductMetadataInterface $productMetadata,
        TypeListInterface $cacheTypeList
    ) {
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
     * @param Observer $observer
     * @return void
     * @throws LocalizedException
     */
    public function execute(Observer $observer)
    {
        $this->validateAccessToken();
        $this->setUserInfo();
        $this->checkBanner('mercadopago_custom');
        $this->checkBanner('mercadopago_customticket');
    }

    /**
     * Check if banner checkout img needs to be updated based on selected country
     *
     * @param $typeCheckout
     */
    public function checkBanner($typeCheckout)
    {
        $country = $this->country;

        if ($country == "") {
            $country = $this->_scopeConfig->getValue(
                ConfigData::PATH_SITE_ID,
                ScopeInterface::SCOPE_WEBSITE,
                $this->_scopeCode
            );
        }

        if (!isset($this->banners[$typeCheckout][$country])) {
            return;
        }

        $defaultBanner = $this->banners[$typeCheckout][$country];
        $currentBanner = $this->_scopeConfig->getValue(
            'payment/' . $typeCheckout . '/banner_checkout',
            ScopeInterface::SCOPE_WEBSITE,
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
     * @throws LocalizedException
     */
    public function setUserInfo()
    {
        $sponsorIdConfig = $this->_scopeConfig->getValue(
            ConfigData::PATH_SPONSOR_ID,
            ScopeInterface::SCOPE_WEBSITE,
            $this->_scopeCode
        );

        $this->coreHelper->log("Sponsor_id: " . $sponsorIdConfig, self::LOG_NAME);

        $sponsorId = "";
        $siteId = "not_defined";

        $this->coreHelper->log("Valid user test", self::LOG_NAME);

        $accessToken = $this->_scopeConfig->getValue(
            ConfigData::PATH_ACCESS_TOKEN,
            ScopeInterface::SCOPE_WEBSITE,
            $this->_scopeCode
        );

        if (!$accessToken) {
            return;
        }

        $mp = $this->coreHelper->getApiInstance($accessToken);
        $user = $mp->get("/users/me");
        $this->coreHelper->log("API Users response", self::LOG_NAME, $user);

        if ($user['status'] == 200) {
            $siteId = $user['response']['site_id'];

            if (!in_array("test_user", $user['response']['tags'])) {
                $countryCode = $user['response']['site_id'];
                $sponsors = [
                    'MLA' => 222568987,
                    'MLB' => 222567845,
                    'MLC' => 222570571,
                    'MCO' => 222570694,
                    'MLM' => 222568246,
                    'MPE' => 222568315,
                    'MLV' => 222569730,
                    'MLU' => 247030424,
                ];

                if (isset($sponsors[$countryCode])) {
                    $sponsorId = $sponsors[$countryCode];
                } else {
                    $sponsorId = '';
                }

                $this->coreHelper->log("Sponsor id set", self::LOG_NAME, $sponsorId);
            }
        }

        $this->_saveWebsiteConfig(ConfigData::PATH_SPONSOR_ID, $sponsorId);
        $this->_saveWebsiteConfig(ConfigData::PATH_SITE_ID, $siteId);

        $this->country = $siteId;
        $this->coreHelper->log("Site_id saved", self::LOG_NAME, $siteId);
        $this->coreHelper->log("Sponsor saved", self::LOG_NAME, $sponsorId);
    }

    /**
     * Validate current accessToken
     *
     * @throws LocalizedException
     */
    protected function validateAccessToken()
    {
        $accessToken = $this->_scopeConfig->getValue(
            ConfigData::PATH_ACCESS_TOKEN,
            ScopeInterface::SCOPE_WEBSITE,
            $this->_scopeCode
        );

        if (!empty($accessToken)) {
            if (!$this->coreHelper->isValidAccessToken($accessToken)) {
                throw new LocalizedException(__('Mercado Pago - Custom Checkout: Invalid access token'));
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
}
