<?php

namespace MercadoPago\Core\Helper;

use Exception;
use Magento\Backend\Block\Store\Switcher;
use Magento\Framework\App\Config\Initial;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Composer\ComposerInformation;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Module\ResourceInterface;
use Magento\Framework\View\LayoutFactory;
use Magento\Payment\Model\Config;
use Magento\Payment\Model\Method\Factory;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\ResourceModel\Status\Collection;
use Magento\Store\Model\App\Emulation;
use Magento\Store\Model\ScopeInterface;
use MercadoPago\Core\Helper\Message\MessageInterface;
use MercadoPago\Core\Lib\Api;
use MercadoPago\Core\Logger\Logger;

/**
 * Class Data
 *
 * @package MercadoPago\Core\Helper
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Data extends \Magento\Payment\Helper\Data
{
    /**
     *api platform openplatform
     */
    const PLATFORM_OPENPLATFORM = 'openplatform';

    /**
     *api platform stdplatform
     */
    const PLATFORM_STD = 'std';

    /**
     *type
     */
    const TYPE = 'magento';

    /**
     * payment calculator
     */
    const STATUS_ACTIVE = 'active';

    const PAYMENT_TYPE_CREDIT_CARD = 'credit_card';

    /**
     * plugins credentials wrapper
     */
    const CREDENTIALS_WRAPPER = '/plugins-credentials-wrapper/credentials';

    /**
     * @var MessageInterface
     */
    protected $_messageInterface;

    /**
     * MercadoPago Logging instance
     *
     * @var Logger
     */
    protected $_mpLogger;

    /**
     * @var Cache
     */
    protected $_mpCache;

    /**
     * @var Collection
     */
    protected $_statusFactory;

    /**
     * @var OrderFactory
     */
    protected $_orderFactory;

    /**
     * @var Switcher
     */
    protected $_switcher;

    /**
     * @var ComposerInformation
     */
    protected $_composerInformation;

    /**
     * @var ResourceInterface $moduleResource
     */
    protected $_moduleResource;

    /**
     * @var Api $api
     */
    protected $_api;

    /**
     * @var ScopeConfigInterface $scopeConfig
     */
    protected $_scopeConfig;

    /**
     * Data constructor.
     * @param Message\MessageInterface $messageInterface
     * @param Cache $mpCache
     * @param Context $context
     * @param LayoutFactory $layoutFactory
     * @param Factory $paymentMethodFactory
     * @param Emulation $appEmulation
     * @param Config $paymentConfig
     * @param Initial $initialConfig
     * @param Logger $logger
     * @param Collection $statusFactory
     * @param OrderFactory $orderFactory
     * @param Switcher $switcher
     * @param ComposerInformation $composerInformation
     * @param ResourceInterface $moduleResource
     * @param Api $api
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        Message\MessageInterface $messageInterface,
        Cache $mpCache,
        Context $context,
        LayoutFactory $layoutFactory,
        Factory $paymentMethodFactory,
        Emulation $appEmulation,
        Config $paymentConfig,
        Initial $initialConfig,
        Logger $logger,
        Collection $statusFactory,
        OrderFactory $orderFactory,
        Switcher $switcher,
        ComposerInformation $composerInformation,
        ResourceInterface $moduleResource,
        Api $api,
        ScopeConfigInterface $scopeConfig
    ) {
        parent::__construct($context, $layoutFactory, $paymentMethodFactory, $appEmulation, $paymentConfig, $initialConfig);
        $this->_messageInterface = $messageInterface;
        $this->_mpLogger = $logger;
        $this->_mpCache = $mpCache;
        $this->_statusFactory = $statusFactory;
        $this->_orderFactory = $orderFactory;
        $this->_switcher = $switcher;
        $this->_composerInformation = $composerInformation;
        $this->_moduleResource = $moduleResource;
        $this->_api = $api;
        $this->_scopeConfig = $scopeConfig;
    }

    /**
     * Log custom message using MercadoPago logger instance
     *
     * @param            $message
     * @param string     $name
     * @param array|null $array
     */
    public function log($message, $name = "mercadopago", $array = null)
    {
        $actionLog = $this->_scopeConfig->getValue(
            ConfigData::PATH_ADVANCED_LOG,
            ScopeInterface::SCOPE_STORE
        );

        if (!$actionLog) {
            return;
        }

        //if extra data is provided, it's encoded for better visualization
        if (!is_null($array)) {
            $message .= " - " . json_encode($array);
        }

        $this->_mpLogger->setName($name);
        $this->_mpLogger->debug($message);
    }

    /**
     * @param null $accessToken
     * @return Api
     * @throws LocalizedException
     */
    public function getApiInstance($publicKey = null, $accessToken = null)
    {
        if (is_null($publicKey) || is_null($accessToken)) {
            throw new LocalizedException(__('The PUBLIC_KEY or ACCESS_TOKEN has not been configured, without this credential the module will not work correctly.'));
        }

        $api = $this->_api;
        $api->set_access_token($accessToken);
        $api->set_public_key($publicKey);
        $api->set_platform(self::PLATFORM_OPENPLATFORM);
        $api->set_type(self::TYPE);

        $api->set_module_version((string)$this->getModuleVersion());
        $api->set_url_store($this->getUrlStore());
        $api->set_email_admin($this->_scopeConfig->getValue('trans_email/ident_sales/email', ScopeInterface::SCOPE_STORE));
        $api->set_country_initial($this->getCountryInitial());

        return $api;
    }

    /**
     * @param $accessToken
     * @return bool
     */
    public function validateCredentials($publicKey, $accessToken)
    {
        $cacheKey = Cache::IS_VALID_PK . $publicKey;
        $cacheToken = Cache::IS_VALID_AT . $accessToken;

        if ($this->_mpCache->getFromCache($cacheToken) && $this->_mpCache->getFromCache($cacheKey)) {
            return true;
        }

        $api = $this->getApiInstance($publicKey, $accessToken);

        $keyResponse = $api->validate_public_key($publicKey);
        $tokenResponse = $api->validade_access_token($accessToken);

        if (!$keyResponse || !$tokenResponse || ($keyResponse['client_id'] !== $tokenResponse['client_id'])) {
            $this->log('Invalid credential pair');
            return false;
        }

        $this->_mpCache->saveCache($cacheKey, true);
        $this->_mpCache->saveCache($cacheToken, true);

        return true;
    }

    /**
     * Calculate and set order MercadoPago specific subtotals based on data values
     *
     * @param $data
     * @param $order Order
     * @throws Exception
     */
    public function setOrderSubtotals($data, $order)
    {
        if (isset($data['total_paid_amount'])) {
            $paidAmount = $this->_getMultiCardValue($data, 'total_paid_amount');
        } else {
            $paidAmount = $data['transaction_details']['total_paid_amount'];
        }

        $shippingCost = $this->_getMultiCardValue($data, 'shipping_cost');

        if ($shippingCost > 0) {
            $order->setBaseShippingAmount($shippingCost);
            $order->setShippingAmount($shippingCost);
        }

        $order->setTotalPaid($paidAmount);
        $order->save();
    }

    /**
     * Return sum of fields separated with |
     *
     * @param $data
     * @param $field
     * @return int
     */
    protected function _getMultiCardValue($data, $field)
    {
        $finalValue = 0;

        if (!isset($data[$field])) {
            return $finalValue;
        }

        $amountValues = explode('|', $data[$field]);
        $statusValues = explode('|', $data['status']);

        foreach ($amountValues as $key => $value) {
            $value = (float)str_replace(' ', '', $value);
            if (str_replace(' ', '', $statusValues[$key]) === 'approved') {
                $finalValue = $finalValue + $value;
            }
        }

        return $finalValue;
    }

    /**
     * return the list of payment methods or false
     *
     * @return array
     */
    public function getMercadoPagoPaymentMethods()
    {
        $publicKey = $this->_scopeConfig->getValue(ConfigData::PATH_PUBLIC_KEY, ScopeInterface::SCOPE_STORE);

        $accessToken = $this->_scopeConfig->getValue(ConfigData::PATH_ACCESS_TOKEN, ScopeInterface::SCOPE_STORE);

        if (!$this->validateCredentials($publicKey, $accessToken)) {
            return [];
        }

        try {
            $mp = $this->getApiInstance($publicKey, $accessToken);

            $payment_methods = $mp->get_payment_methods($publicKey);
        } catch (Exception $e) {
            return [];
        }

        return $payment_methods;
    }

    /**
     * Get initial country
     *
     * @return string
     */
    public function getCountryInitial()
    {
        try {
            $objectManager = ObjectManager::getInstance();
            $store = $objectManager->get('Magento\Framework\Locale\Resolver');
            $locale = $store->getLocale();
            $locale = explode("_", $locale);

            return $locale[1];
        } catch (Exception $e) {
            return "US";
        }
    }

    /**
     * Get store URL
     *
     * @return string
     */
    public function getUrlStore()
    {
        try {
            $objectManager = ObjectManager::getInstance(); //instance of\Magento\Framework\App\ObjectManager
            $storeManager = $objectManager->get('Magento\Store\Model\StoreManagerInterface');
            $currentStore = $storeManager->getStore();

            return $currentStore->getBaseUrl();
        } catch (Exception $e) {
            return "";
        }
    }

    /**
     * Get module version
     *
     * @return string
     */
    public function getModuleVersion()
    {
        return $this->_moduleResource->getDbVersion('MercadoPago_Core');
    }

    /**
     * Get modal link
     *
     * @param string $localization
     * @return string
     */
    public function getWalletButtonLink($localization)
    {
        $site_id = [
            'MCO' => 'https://www.mercadopago.com.co/integrations/v1/web-payment-checkout.js',
            'MLA' => 'https://www.mercadopago.com.ar/integrations/v1/web-payment-checkout.js',
            'MLB' => 'https://www.mercadopago.com.br/integrations/v1/web-payment-checkout.js',
            'MLC' => 'https://www.mercadopago.cl/integrations/v1/web-payment-checkout.js',
            'MLM' => 'https://www.mercadopago.com.mx/integrations/v1/web-payment-checkout.js',
            'MLU' => 'https://www.mercadopago.com.uy/integrations/v1/web-payment-checkout.js',
            'MLV' => 'https://www.mercadopago.com.ve/integrations/v1/web-payment-checkout.js',
            'MPE' => 'https://www.mercadopago.com.pe/integrations/v1/web-payment-checkout.js',
        ];

        if (array_key_exists($localization, $site_id)) {
            return $site_id[$localization];
        }

        return $site_id['MLA'];
    }

    /**
     * Get finger print link
     *
     * @param string $localization
     * @return string
     */
    public function getFingerPrintLink($localization)
    {
        $site_id = [
            'MLA' => 'https://www.mercadopago.com.ar/ayuda/terminos-y-politicas_194',
            'MLB' => 'https://www.mercadopago.com.br/ajuda/termos-e-politicas_194',
            'MLC' => 'https://www.mercadopago.cl/ayuda/terminos-y-politicas_194',
            'MLM' => 'https://www.mercadopago.com.mx/ayuda/terminos-y-politicas_194',
            'MLU' => 'https://www.mercadopago.com.uy/ayuda/terminos-y-politicas_194',
            'MPE' => 'https://www.mercadopago.com.pe/ayuda/terminos-y-politicas_194',
            'MCO' => 'https://www.mercadopago.com.co/ayuda/terminos-y-politicas_194',
        ];

        if (array_key_exists($localization, $site_id)) {
            return $site_id[$localization];
        }

        return $site_id['MLA'];
    }
}
