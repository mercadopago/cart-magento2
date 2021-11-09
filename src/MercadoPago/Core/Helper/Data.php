<?php

namespace MercadoPago\Core\Helper;

use Exception;
use Magento\Backend\Block\Store\Switcher;
use Magento\Framework\App\Config\Initial;
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
use MercadoPago\Core\Lib\RestClient;
use MercadoPago\Core\Logger\Logger;
use MercadoPago\Core\Model\Custom\Payment;
use MercadoPago\Core\Helper\PaymentPlaces;

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
    protected $_composerInformation;

    /**
     * @var ResourceInterface $moduleResource
     */
    protected $_moduleResource;

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
        ResourceInterface $moduleResource
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
        //load admin configuration value, default is true
        $actionLog = $this->scopeConfig->getValue(
            \MercadoPago\Core\Helper\ConfigData::PATH_ADVANCED_LOG,
            ScopeInterface::SCOPE_STORE
        );

        if (!$actionLog) {
            return;
        }

        //if extra data is provided, it's encoded for better visualization
        if (!is_null($array)) {
            $message .= " - " . json_encode($array);
        }

        //set log
        $this->_mpLogger->setName($name);
        $this->_mpLogger->debug($message);
    }

    /**
     * @param null $accessToken
     * @return Api
     * @throws LocalizedException
     */
    public function getApiInstance($accessToken = null)
    {
        if (is_null($accessToken)) {
            throw new LocalizedException(__('The ACCESS_TOKEN has not been configured, without this credential the module will not work correctly.'));
        }

        $api = new Api($accessToken);
        $api->set_platform(self::PLATFORM_OPENPLATFORM);

        $api->set_type(self::TYPE);
        RestClient::setModuleVersion((string)$this->getModuleVersion());
        RestClient::setUrlStore($this->getUrlStore());
        RestClient::setEmailAdmin($this->scopeConfig->getValue('trans_email/ident_sales/email', ScopeInterface::SCOPE_STORE));
        RestClient::setCountryInitial($this->getCountryInitial());
        RestClient::setSponsorID($this->scopeConfig->getValue('payment/mercadopago/sponsor_id', ScopeInterface::SCOPE_STORE));

        //$api->set_so((string)$this->_moduleContext->getVersion()); //TODO tracking

        return $api;
    }

    /**
     * @param $accessToken
     * @return bool
     * @throws LocalizedException
     */
    public function isValidAccessToken($accessToken)
    {
        $cacheKey = Cache::IS_VALID_AT . $accessToken;

        if ($this->_mpCache->getFromCache($cacheKey)) {
            return true;
        }

        $mp = $this->getApiInstance($accessToken);
        $isValid = $mp->is_valid_access_token();

        $this->_mpCache->saveCache($cacheKey, $isValid);
        return $isValid;
    }

    /**
     * ClientId and Secret valid?
     *
     * @param $clientId
     * @param $clientSecret
     *
     * @return bool
     * @throws LocalizedException
     */
    public function isValidClientCredentials($clientId, $clientSecret)
    {
        $mp = $this->getApiInstance($clientId, $clientSecret);
        try {
            $mp->get_access_token();
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * @param string $scopeCode
     * @return bool|mixed
     */
    public function getAccessToken($scopeCode = ScopeInterface::SCOPE_STORE)
    {
        $accessToken = $this->scopeConfig->getValue(\MercadoPago\Core\Helper\ConfigData::PATH_ACCESS_TOKEN, $scopeCode);
        if (empty($accessToken)) {
            return false;
        }

        return $accessToken;
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
     * Modify payment array adding specific fields
     *
     * @param $payment
     *
     * @return mixed
     * @refactor
     */
    public function setPayerInfo(&$payment)
    {
        $this->log("setPayerInfo", 'mercadopago-custom.log', $payment);

        if ($payment['payment_method_id']) {
            $payment["payment_method"] = $payment['payment_method_id'];
        }

        if ($payment['installments']) {
            $payment["installments"] = $payment['installments'];
        }
        if ($payment['id']) {
            $payment["payment_id_detail"] = $payment['id'];
        }
        if (isset($payment['trunc_card'])) {
            $payment["trunc_card"] = $payment['trunc_card'];
        } elseif (isset($payment['card']) && isset($payment['card']['last_four_digits'])) {
            $payment["trunc_card"] = "xxxx xxxx xxxx " . $payment['card']["last_four_digits"];
        }

        if (isset($payment['card']["cardholder"]["name"])) {
            $payment["cardholder_name"] = $payment['card']["cardholder"]["name"];
        }

        if (isset($payment['payer']['first_name'])) {
            $payment['payer_first_name'] = $payment['payer']['first_name'];
        }

        if (isset($payment['payer']['last_name'])) {
            $payment['payer_last_name'] = $payment['payer']['last_name'];
        }

        if (isset($payment['payer']['email'])) {
            $payment['payer_email'] = $payment['payer']['email'];
        }

        return $payment;
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
     * return the list of payment methods or null
     *
     * @param mixed|null $accessToken
     *
     * @return mixed
     */
    public function getMercadoPagoPaymentMethods($accessToken)
    {
        try {
            $mp = $this->getApiInstance($accessToken);

            $payment_methods = $mp->get("/v1/payment_methods");

            foreach ($payment_methods['response'] as $payment_method) {
                if (!isset($payment_method['payment_places'])) {
                    $payment_method['payment_places'] = PaymentPlaces::getPaymentPlaces($payment_method['id']);
                }
            }

        } catch (Exception $e) {
            return false;
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
     * Summary: Get client id from access token.
     * Description: Get client id from access token.
     *
     * @param String $at
     *
     * @return String client id.
     */
    public static function getClientIdFromAccessToken($at)
    {
        $t = explode('-', $at);

        if (count($t) > 0) {
            return $t[1];
        }

        return '';
    }

    /**
     * @param $additionalInfo
     * @return string|null
     */
    public function getPaymentId($additionalInfo)
    {
        if (isset($additionalInfo['payment_id_detail']) && !empty($additionalInfo['payment_id_detail'])) {
            return $additionalInfo['payment_id_detail'];
        }

        if (isset($additionalInfo['paymentResponse']) && !empty($additionalInfo['paymentResponse'])) {
            return $additionalInfo['paymentResponse']['id'];
        }

        return null;
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
