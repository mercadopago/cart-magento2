<?php

namespace MercadoPago\Core\Model\Custom;

use Magento\Customer\Model\Session;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\Context;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\UrlInterface;
use Magento\Payment\Helper\Data;
use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Payment\Model\Method\Cc;
use Magento\Payment\Model\Method\ConfigInterface;
use Magento\Payment\Model\Method\Logger;
use Magento\Payment\Model\Method\Online\GatewayInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\OrderFactory;
use Magento\Store\Model\ScopeInterface;
use MercadoPago\Core\Helper\ConfigData;
use MercadoPago\Core\Helper\Response;
use MercadoPago\Core\Model\Api\V1\Exception;
use MercadoPago\Core\Model\Core;

/**
 * Class Payment
 *
 * @package                                        MercadoPago\Core\Model\Custom
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Payment extends Cc implements GatewayInterface
{
    /**
     * Define payment method code
     */
    const CODE = 'mercadopago_custom';

    /**
     * @var string
     */
    protected $_code = self::CODE;

    /**
     * Availability option
     *
     * @var boolean
     */
    protected $_isGateway = true;

    /**
     * Availability option
     *
     * @var boolean
     */
    protected $_canAuthorize = true;

    /**
     * Availability option
     *
     * @var boolean
     */
    protected $_canCapture = true;

    /**
     * Availability option
     *
     * @var boolean
     */
    protected $_canCapturePartial = true;

    /**
     * Availability option
     *
     * @var boolean
     */
    protected $_canRefund = true;

    /**
     * Availability option
     *
     * @var boolean
     */
    protected $_canRefundInvoicePartial = true;

    /**
     * Availability option
     *
     * @var boolean
     */
    protected $_canVoid = true;

    /**
     * Availability option
     *
     * @var boolean
     */
    protected $_canUseInternal = false;

    /**
     * Availability option
     *
     * @var boolean
     */
    protected $_canUseCheckout = true;

    /**
     * Availability option
     *
     * @var boolean
     */
    protected $_canSaveCc = false;

    /**
     * Availability option
     *
     * @var boolean
     */
    protected $_isProxy = false;

    /**
     * Availability option
     *
     * @var boolean
     */
    protected $_canFetchTransactionInfo = true;

    /**
     * Payment Method feature
     *
     * @var boolean
     */
    protected $_canReviewPayment = true;

    /**
     * Payment Method feature
     *
     * @var boolean
     */
    protected $_isInitializeNeeded = true;

    /**
     * @var Core
     */
    protected $_coreModel;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * @var Session
     */
    protected $_customerSession;

    /**
     * @var OrderFactory
     */
    protected $_orderFactory;

    /**
     * @var UrlInterface
     */
    protected $_urlBuilder;

    /**
     * @var \MercadoPago\Core\Helper\Data
     */
    protected $_helperData;

    /**
     *
     */
    const LOG_NAME = 'custom_payment';

    /**
     * @var string
     */
    protected $_accessToken;

    /**
     * @var string
     */
    protected $_publicKey;

    /**
     * @var array
     */
    public static $_excludeInputsOpc = [
        'issuer_id',
        'card_expiration_month',
        'card_expiration_year',
        'card_holder_name',
        'doc_type',
        'doc_number',
    ];

    /**
     * @var string
     */
    protected $_infoBlockType = 'MercadoPago\Core\Block\Info';

    /**
     * Request object
     *
     * @var RequestInterface
     */
    protected $_request;

    /**
     * @param                                          \MercadoPago\Core\Helper\Data   $helperData
     * @param                                          \Magento\Checkout\Model\Session $checkoutSession
     * @param                                          Session                         $customerSession
     * @param                                          OrderFactory                    $orderFactory
     * @param                                          UrlInterface                    $urlBuilder
     * @param                                          Context                         $context
     * @param                                          Registry                        $registry
     * @param                                          ExtensionAttributesFactory      $extensionFactory
     * @param                                          AttributeValueFactory           $customAttributeFactory
     * @param                                          Data                            $paymentData
     * @param                                          ScopeConfigInterface            $scopeConfig
     * @param                                          Logger                          $logger
     * @param                                          ModuleListInterface             $moduleList
     * @param                                          TimezoneInterface               $localeDate
     * @param                                          Core                            $coreModel
     * @param                                          RequestInterface                $request
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \MercadoPago\Core\Helper\Data $helperData,
        \Magento\Checkout\Model\Session $checkoutSession,
        Session $customerSession,
        OrderFactory $orderFactory,
        UrlInterface $urlBuilder,
        Context $context,
        Registry $registry,
        ExtensionAttributesFactory $extensionFactory,
        AttributeValueFactory $customAttributeFactory,
        Data $paymentData,
        ScopeConfigInterface $scopeConfig,
        Logger $logger,
        ModuleListInterface $moduleList,
        TimezoneInterface $localeDate,
        Core $coreModel,
        RequestInterface $request
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $moduleList,
            $localeDate
        );

        $this->_helperData      = $helperData;
        $this->_coreModel       = $coreModel;
        $this->_checkoutSession = $checkoutSession;
        $this->_customerSession = $customerSession;
        $this->_orderFactory    = $orderFactory;
        $this->_urlBuilder      = $urlBuilder;
        $this->_request         = $request;
        $this->_scopeConfig     = $scopeConfig;
    }//end __construct()

    /**
     * {inheritdoc}
     */
    public function postRequest(DataObject $request, ConfigInterface $config)
    {
        return '';
    }//end postRequest()

    /**
     * @param  DataObject $data
     * @return $this|Cc
     * @throws LocalizedException
     */
    public function assignData(\Magento\Framework\DataObject $data)
    {
        if (!($data instanceof \Magento\Framework\DataObject)) {
            $data = new \Magento\Framework\DataObject($data);
        }

        $infoForm = $data->getData();

        if (isset($infoForm['additional_data'])) {
            if (empty($infoForm['additional_data'])) {
                return $this;
            }

            $additionalData = $infoForm['additional_data'];

            if (isset($additionalData['one_click_pay']) && $additionalData['one_click_pay'] == 1) {
                $additionalData = $this->cleanFieldsOcp($additionalData);
            }

            if (empty($additionalData['token'])) {
                $this->_helperData->log('CustomPayment::assignData - Token for payment creation was not generated, therefore it is not possible to continue the transaction');
                throw new LocalizedException(__(Response::PAYMENT_CREATION_ERRORS['TOKEN_EMPTY']));
            }

            $info = $this->getInfoInstance();

            $info->setAdditionalInformation($additionalData);
            $info->setAdditionalInformation('method', $infoForm['method']);
            $info->setAdditionalInformation('payment_type_id', 'credit_card');
            $info->setAdditionalInformation('payment_method', $additionalData['payment_method_id']);
            $info->setAdditionalInformation('cardholderName', $additionalData['card_holder_name']);

            if (!empty($additionalData['card_expiration_month']) && !empty($additionalData['card_expiration_year'])) {
                $info->setAdditionalInformation('expiration_date', $additionalData['card_expiration_month'] . '/' . $additionalData['card_expiration_year']);
            }

            if (isset($additionalData['gateway_mode'])) {
                $info->setAdditionalInformation('gateway_mode', $additionalData['gateway_mode']);
            }
        }//end if

        return $this;
    }//end assignData()

    /**
     * @param  string $paymentAction
     * @param  object $stateObject
     * @return $this|bool|Cc
     * @throws LocalizedException
     * @throws Exception
     */
    public function initialize($paymentAction, $stateObject)
    {
        if ($this->getInfoInstance()->getAdditionalInformation('token') == '') {
            $this->_helperData->log('CustomPayment::initialize - Token for payment creation was not generated, therefore it is not possible to continue the transaction');
            throw new LocalizedException(__(Response::PAYMENT_CREATION_ERRORS['TOKEN_EMPTY']));
        }

        $preference = $this->createCustomPreference();
        return $this->createCustomPayment($preference);
    }//end initialize()

    /**
     * @throws LocalizedException
     */
    public function createCustomPreference()
    {
        try {
            $order       = $this->getInfoInstance()->getOrder();
            $payment     = $order->getPayment();
            $paymentInfo = $this->getPaymentInfo($payment);

            $preference                 = $this->_coreModel->makeDefaultPreferencePaymentV1($paymentInfo, $this->_getQuote(), $order);
            $preference['installments'] = (int) $payment->getAdditionalInformation('installments');
            $paymentMethod              = $payment->getAdditionalInformation('payment_method_id');
            if ($paymentMethod == '') {
                $paymentMethod = $payment->getAdditionalInformation('payment_method_selector');
            }

            $preference['payment_method_id'] = $paymentMethod;
            $preference['token']             = $payment->getAdditionalInformation('token');
            $preference['metadata']['token'] = $payment->getAdditionalInformation('token');

            if ($payment->getAdditionalInformation('issuer_id') != '' && $payment->getAdditionalInformation('issuer_id') > -1) {
                $preference['issuer_id'] = (int) $payment->getAdditionalInformation('issuer_id');
            }

            if ($payment->getAdditionalInformation('gateway_mode')) {
                $preference['processing_mode'] = 'gateway';
            }

            $preference['binary_mode']          = $this->_scopeConfig->isSetFlag(ConfigData::PATH_CUSTOM_BINARY_MODE);
            $preference['statement_descriptor'] = $this->_scopeConfig->getValue(ConfigData::PATH_CUSTOM_STATEMENT_DESCRIPTOR);

            $preference['metadata']['checkout']      = 'custom';
            $preference['metadata']['checkout_type'] = 'credit_card';

            $this->_helperData->log('CustomPayment::initialize - Credit Card: Preference to POST /v1/payments', self::LOG_NAME, $preference);
            return $preference;
        } catch (\Exception $e) {
            $this->_helperData->log('CustomPayment::initialize - There was an error retrieving the information to create the payment, more details: ' . $e->getMessage());
            throw new LocalizedException(__(Response::PAYMENT_CREATION_ERRORS['INTERNAL_ERROR_MODULE']));
        }//end try
    }//end createCustomPreference()

    /**
     * @param  $preference
     * @return $this|bool
     * @throws LocalizedException
     * @throws Exception
     */
    public function createCustomPayment($preference)
    {
        $response = $this->_coreModel->postPaymentV1($preference);
        if (isset($response['status']) && ($response['status'] == 200 || $response['status'] == 201)) {
            $this->getInfoInstance()->setAdditionalInformation('paymentResponse', $response['response']);
            return true;
        }

        $messageErrorToClient = $this->_coreModel->getMessageError($response);
        $arrayLog             = [
            'response' => $response,
            'message'  => $messageErrorToClient,
        ];
        $this->_helperData->log('CustomPayment::initialize - The API returned an error while creating the payment, more details: ' . json_encode($arrayLog));
        throw new LocalizedException(__($messageErrorToClient));
    }//end createCustomPayment()

    /**
     * @return $this
     * @throws LocalizedException
     */
    public function validate()
    {
        AbstractMethod::validate();

        return $this;
    }//end validate()

    /**
     * Retrieves quote
     *
     * @return Quote
     */
    protected function _getQuote()
    {
        return $this->_checkoutSession->getQuote();
    }//end _getQuote()

    /**
     * Retrieves Order
     *
     * @param $incrementId
     *
     * @return mixed
     */
    protected function _getOrder($incrementId)
    {
        return $this->_orderFactory->create()->loadByIncrementId($incrementId);
    }//end _getOrder()

    /**
     * Return success page url
     *
     * @return string
     */
    public function getOrderPlaceRedirectUrl()
    {
        $url = 'mercadopago/checkout/page';
        return $this->_urlBuilder->getUrl($url, ['_secure' => true]);
    }//end getOrderPlaceRedirectUrl()

    /**
     * @param  CartInterface|null $quote
     * @return boolean
     */
    public function isAvailable(CartInterface $quote=null)
    {
        $isActive = $this->_scopeConfig->getValue(ConfigData::PATH_CUSTOM_ACTIVE, ScopeInterface::SCOPE_STORE);
        if (empty($isActive)) {
            return false;
        }

        $publicKey = $this->_scopeConfig->getValue(ConfigData::PATH_PUBLIC_KEY, ScopeInterface::SCOPE_STORE);
        if (empty($publicKey)) {
            $this->_helperData->log('CustomPayment::isAvailable - Module not available because public_key has not been configured.');
            return false;
        }

        $accessToken = $this->_scopeConfig->getValue(ConfigData::PATH_ACCESS_TOKEN, ScopeInterface::SCOPE_STORE);
        if (empty($accessToken)) {
            $this->_helperData->log('CustomPayment::isAvailable - Module not available because access_token has not been configured.');
            return false;
        }

        $secure = $this->_request->isSecure();
        if ($secure === false && substr($publicKey, 0, 5) !== 'TEST-' && substr($accessToken, 0, 5) !== 'TEST-') {
            $this->_helperData->log('CustomPayment::isAvailable - Module not available because it has production credentials in non HTTPS environment.');
            return false;
        }

        return $this->available($quote);
    }//end isAvailable()

    /**
     * @param  CartInterface|null $quote
     * @return boolean
     * @throws LocalizedException
     */
    public function isAvailableMethod(CartInterface $quote=null)
    {
        return $this->available($quote);
    }//end isAvailableMethod()

    /**
     * @return boolean
     * @throws LocalizedException
     */
    public function available(CartInterface $quote=null)
    {
        $parent = parent::isAvailable($quote);
        $status = true;

        if (!$parent) {
            $this->_helperData->log('CustomPayment::isAvailable - Module not available due to magento rules.');
            $status = false;
        }

        $accessToken = $this->_scopeConfig->getValue(ConfigData::PATH_ACCESS_TOKEN, ScopeInterface::SCOPE_STORE);
        if (empty($accessToken)) {
            $this->_helperData->log('CustomPayment::isAvailable - Module not available because access_token has not been configured.');
            return false;
        }

        $public_key = $this->_scopeConfig->getValue(ConfigData::PATH_PUBLIC_KEY, ScopeInterface::SCOPE_STORE);
        if (empty($public_key)) {
            $this->_helperData->log('CustomPayment::isAvailable - Module not available because public_key has not been configured.');
            return false;
        }

        if (!$this->_helperData->isValidAccessToken($accessToken)) {
            $this->_helperData->log('CustomPayment::isAvailable - Module not available because access_token is not valid.');
            return false;
        }

        return $status;
    }//end available()

    /**
     * Get stored customers and cards from api
     *
     * @return mixed
     */
    public function getCustomerAndCards()
    {
        $email = $this->_coreModel->getEmailCustomer();

        $customer = $this->getOrCreateCustomer($email);

        return $customer;
    }//end getCustomerAndCards()

    /**
     * Saves customer and its corresponding card
     *
     * @param $token
     * @param $payment_created
     */
    public function customerAndCards($token, $payment_created)
    {
        $customer = $this->getOrCreateCustomer($payment_created['payer']['email']);

        if ($customer !== false) {
            $this->checkAndcreateCard($customer, $token, $payment_created);
        }
    }//end customerAndCards()

    /**
     * Saves customer tokenized card to be used later by OCP
     *
     * @param $customer
     * @param $token
     * @param $payment
     *
     * @return array|boolean
     * @throws LocalizedException
     */
    public function checkAndcreateCard($customer, $token, $payment)
    {
        $accessToken = $this->_scopeConfig->getValue(ConfigData::PATH_ACCESS_TOKEN, ScopeInterface::SCOPE_STORE);

        $mp = $this->_helperData->getApiInstance($accessToken);

        foreach ($customer['cards'] as $card) {
            if ($card['first_six_digits'] == $payment['card']['first_six_digits']
                && $card['last_four_digits'] == $payment['card']['last_four_digits']
                && $card['expiration_month'] == $payment['card']['expiration_month']
                && $card['expiration_year'] == $payment['card']['expiration_year']
            ) {
                $this->_helperData->log('Card already exists', self::LOG_NAME, $card);

                return $card;
            }
        }

        $params = ['token' => $token];
        if (isset($payment['issuer_id'])) {
            $params['issuer_id'] = (int) $payment['issuer_id'];
        }

        if (isset($payment['payment_method_id'])) {
            $params['payment_method_id'] = $payment['payment_method_id'];
        }

        $card = $mp->post('/v1/customers/' . $customer['id'] . '/cards', $params);

        $this->_helperData->log('Response create card', self::LOG_NAME, $card);

        if ($card['status'] == 201) {
            return $card['response'];
        }

        return false;
    }//end checkAndcreateCard()

    /**
     * Saves to be used later by OCP
     *
     * @param $email
     *
     * @return boolean|array
     * @throws LocalizedException
     */
    public function getOrCreateCustomer($email)
    {
        if (empty($email)) {
            return false;
        }

        // get access_token
        if (!$this->_accessToken) {
            $this->_accessToken = $this->_scopeConfig->getValue(ConfigData::PATH_ACCESS_TOKEN, ScopeInterface::SCOPE_STORE);
        }

        $mp = $this->_helperData->getApiInstance($this->_accessToken);

        $customer = $mp->get('/v1/customers/search', ['email' => $email]);

        $this->_helperData->log('Response search customer', self::LOG_NAME, $customer);

        if ($customer['status'] == 200) {
            if ($customer['response']['paging']['total'] > 0) {
                return $customer['response']['results'][0];
            } else {
                $this->_helperData->log('Customer not found: ' . $email, self::LOG_NAME);

                $customer = $mp->post('/v1/customers', ['email' => $email]);

                $this->_helperData->log('Response create customer', self::LOG_NAME, $customer);

                if ($customer['status'] == 201) {
                    return $customer['response'];
                } else {
                    return false;
                }
            }
        } else {
            return false;
        }
    }//end getOrCreateCustomer()

    /**
     * @param $info
     *
     * @return mixed
     */
    protected function cleanFieldsOcp($info)
    {
        foreach (self::$_excludeInputsOpc as $field) {
            $info[$field] = '';
        }

        return $info;
    }//end cleanFieldsOcp()

    /**
     * Set info to payment object
     *
     * @param $payment
     *
     * @return array
     */
    protected function getPaymentInfo($payment)
    {
        $payment_info = [];

        if ($payment->getAdditionalInformation('doc_number') != '') {
            $payment_info['identification_type']   = $payment->getAdditionalInformation('doc_type');
            $payment_info['identification_number'] = $payment->getAdditionalInformation('doc_number');
        }

        return $payment_info;
    }//end getPaymentInfo()
}//end class
