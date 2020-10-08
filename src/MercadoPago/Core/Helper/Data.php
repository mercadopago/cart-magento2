<?php

namespace MercadoPago\Core\Helper;

use Magento\Framework\View\LayoutFactory;


/**
 * Class Data
 *
 * @package MercadoPago\Core\Helper
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Data
    extends \Magento\Payment\Helper\Data
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
    //end const platform

    /**
     * payment calculator
     */
    const STATUS_ACTIVE = 'active';
    const PAYMENT_TYPE_CREDIT_CARD = 'credit_card';

    /**
     * @var \MercadoPago\Core\Helper\Message\MessageInterface
     */
    protected $_messageInterface;

    /**
     * MercadoPago Logging instance
     *
     * @var \MercadoPago\Core\Logger\Logger
     */
    protected $_mpLogger;

    /**
     * @var \MercadoPago\Core\Helper\Cache
     */
    protected $_mpCache;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Status\Collection
     */
    protected $_statusFactory;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $_orderFactory;

    /**
     * @var \Magento\Backend\Block\Store\Switcher
     */
    protected $_switcher;
    protected $_composerInformation;


    /**
     * @var \Magento\Framework\Module\ResourceInterface $moduleResource
     */
    protected $_moduleResource;

    /**
     * Data constructor.
     * @param Message\MessageInterface $messageInterface
     * @param Cache $mpCache
     * @param \Magento\Framework\App\Helper\Context $context
     * @param LayoutFactory $layoutFactory
     * @param \Magento\Payment\Model\Method\Factory $paymentMethodFactory
     * @param \Magento\Store\Model\App\Emulation $appEmulation
     * @param \Magento\Payment\Model\Config $paymentConfig
     * @param \Magento\Framework\App\Config\Initial $initialConfig
     * @param \MercadoPago\Core\Logger\Logger $logger
     * @param \Magento\Sales\Model\ResourceModel\Status\Collection $statusFactory
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Magento\Backend\Block\Store\Switcher $switcher
     * @param \Magento\Framework\Composer\ComposerInformation $composerInformation
     * @param \Magento\Framework\Module\ResourceInterface $moduleResource
     */
    public function __construct(
        Message\MessageInterface $messageInterface,
        Cache $mpCache,
        \Magento\Framework\App\Helper\Context $context,
        LayoutFactory $layoutFactory,
        \Magento\Payment\Model\Method\Factory $paymentMethodFactory,
        \Magento\Store\Model\App\Emulation $appEmulation,
        \Magento\Payment\Model\Config $paymentConfig,
        \Magento\Framework\App\Config\Initial $initialConfig,
        \MercadoPago\Core\Logger\Logger $logger,
        \Magento\Sales\Model\ResourceModel\Status\Collection $statusFactory,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Backend\Block\Store\Switcher $switcher,
        \Magento\Framework\Composer\ComposerInformation $composerInformation,
        \Magento\Framework\Module\ResourceInterface $moduleResource

    )
    {

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
     * @param        $message
     * @param string $name
     * @param null $array
     */
    public function log($message, $name = "mercadopago", $array = null)
    {
        //load admin configuration value, default is true
        $actionLog = $this->scopeConfig->getValue(\MercadoPago\Core\Helper\ConfigData::PATH_ADVANCED_LOG, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
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
     * @return \MercadoPago\Core\Lib\Api
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getApiInstance($accessToken = null)
    {

        if (is_null($accessToken)) {
            throw new \Magento\Framework\Exception\LocalizedException(__('The ACCESS_TOKEN has not been configured, without this credential the module will not work correctly.'));
        }

        $api = new \MercadoPago\Core\Lib\Api($accessToken);
        $api->set_platform(self::PLATFORM_OPENPLATFORM);

        $api->set_type(self::TYPE);
        \MercadoPago\Core\Lib\RestClient::setModuleVersion((string)$this->getModuleVersion());
        \MercadoPago\Core\Lib\RestClient::setUrlStore($this->getUrlStore());
        \MercadoPago\Core\Lib\RestClient::setEmailAdmin($this->scopeConfig->getValue('trans_email/ident_sales/email', \Magento\Store\Model\ScopeInterface::SCOPE_STORE));
        \MercadoPago\Core\Lib\RestClient::setCountryInitial($this->getCountryInitial());
        \MercadoPago\Core\Lib\RestClient::setSponsorID($this->scopeConfig->getValue('payment/mercadopago/sponsor_id', \Magento\Store\Model\ScopeInterface::SCOPE_STORE));

        //$api->set_so((string)$this->_moduleContext->getVersion()); //TODO tracking

        return $api;

    }

    /**
     * @param $accessToken
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
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
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function isValidClientCredentials($clientId, $clientSecret)
    {
        $mp = $this->getApiInstance($clientId, $clientSecret);
        try {
            $mp->get_access_token();
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * @param string $scopeCode
     * @return bool|mixed
     */
    public function getAccessToken($scopeCode = \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
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
     * @param $order
     */
    public function setOrderSubtotals($data, $order)
    {
        $couponAmount = $this->_getMultiCardValue($data, 'coupon_amount');
        $transactionAmount = $this->_getMultiCardValue($data, 'transaction_amount');

        if (isset($data['total_paid_amount'])) {
            $paidAmount = $this->_getMultiCardValue($data, 'total_paid_amount');
        } else {
            $paidAmount = $data['transaction_details']['total_paid_amount'];
        }

        $shippingCost = $this->_getMultiCardValue($data, 'shipping_cost');
        $originalAmount = $transactionAmount + $shippingCost;

        if ($couponAmount
            && $this->scopeConfig->isSetFlag(\MercadoPago\Core\Helper\ConfigData::PATH_ADVANCED_CONSIDER_DISCOUNT, \Magento\Store\Model\ScopeInterface::SCOPE_STORE)) {
            $order->setDiscountCouponAmount($couponAmount * -1);
            $order->setBaseDiscountCouponAmount($couponAmount * -1);
            $financingCost = $paidAmount + $couponAmount - $originalAmount;
        } else {
            //if a discount was applied and should not be considered
            $paidAmount += $couponAmount;
            $financingCost = $paidAmount - $originalAmount;
        }

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
        } else if (isset($payment['card']) && isset($payment['card']['last_four_digits'])) {
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
     * @param $fullValue
     *
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
            if (str_replace(' ', '', $statusValues[$key]) == 'approved') {
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

            $response = $mp->get("/v1/payment_methods");
            if ($response['status'] == 401 || $response['status'] == 400) {
                return false;
            }
        } catch (\Exception $e) {
            return false;
        }

        return $response['response'];
    }

    public function getCountryInitial()
    {
        try {

            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $store = $objectManager->get('Magento\Framework\Locale\Resolver');
            $locale = $store->getLocale();
            $locale = explode("_", $locale);
            $locale = $locale[1];

            return $locale;

        } catch (\Exception $e) {
            return "US";
        }
    }

    public function getUrlStore()
    {

        try {

            $objectManager = \Magento\Framework\App\ObjectManager::getInstance(); //instance of\Magento\Framework\App\ObjectManager
            $storeManager = $objectManager->get('Magento\Store\Model\StoreManagerInterface');
            $currentStore = $storeManager->getStore();
            $baseUrl = $currentStore->getBaseUrl();
            return $baseUrl;

        } catch (\Exception $e) {
            return "";
        }

    }

    public function getModuleVersion()
    {
        $version = $this->_moduleResource->getDbVersion('MercadoPago_Core');
        return $version;
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
     * @param $order
     * @return array
     */
    public function getAnalyticsData($order)
    {
        $analyticsData = [];

        if (!empty($order->getPayment())) {
            $additionalInfo = $order->getPayment()->getData('additional_information');

            if ($order->getPayment()->getData('method')) {
                $accessToken = $this->scopeConfig->getValue(\MercadoPago\Core\Helper\ConfigData::PATH_ACCESS_TOKEN,
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
                $publicKey = $this->scopeConfig->getValue(\MercadoPago\Core\Helper\ConfigData::PATH_PUBLIC_KEY,
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
                $methodCode = $order->getPayment()->getData('method');
                $analyticsData = [
                    'payment_id' => $this->getPaymentId($additionalInfo),
                    'payment_type' => $additionalInfo['payment_method_id'],
                    'checkout_type' => $additionalInfo['method'],
                    'analytics_key' => $this->getClientIdFromAccessToken($accessToken)
                ];
                if ($methodCode == \MercadoPago\Core\Model\Custom\Payment::CODE) {
                    $analyticsData['public_key'] = $publicKey;
                }
            }
        }

        return $analyticsData;
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
}
