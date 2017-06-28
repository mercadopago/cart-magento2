<?php
namespace MercadoPago\Core\Model\Custom;

use Magento\Framework\DataObject;
use Magento\Payment\Model\Method\Online\GatewayInterface;
use Magento\Payment\Model\Method\ConfigInterface;

/**
 * Class Payment
 *
 * @package MercadoPago\Core\Model\Custom
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Payment
    extends \Magento\Payment\Model\Method\Cc
    implements GatewayInterface
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
     * @var bool
     */
    protected $_isGateway = true;

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_canAuthorize = true;

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_canCapture = true;

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_canCapturePartial = true;

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_canRefund = true;

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_canRefundInvoicePartial = true;

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_canVoid = true;

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_canUseInternal = false;

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_canUseCheckout = true;

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_canSaveCc = false;

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_isProxy = false;

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_canFetchTransactionInfo = true;

    /**
     * Payment Method feature
     *
     * @var bool
     */
    protected $_canReviewPayment = true;

    /**
     * Payment Method feature
     *
     * @var bool
     */
    protected $_isInitializeNeeded = true;

    /**
     * @var \MercadoPago\Core\Model\Core
     */
    protected $_coreModel;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $_orderFactory;

    /**
     * @var \Magento\Framework\UrlInterface
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
    public static $_excludeInputsOpc = ['issuer_id', 'card_expiration_month', 'card_expiration_year', 'card_holder_name', 'doc_type', 'doc_number'];

    /**
     * @var string
     */
    protected $_infoBlockType = 'MercadoPago\Core\Block\Info';

    /**
     * Request object
     *
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $_request;

    /**
     * @param \MercadoPago\Core\Helper\Data                        $helperData
     * @param \Magento\Checkout\Model\Session                      $checkoutSession
     * @param \Magento\Customer\Model\Session                      $customerSession
     * @param \Magento\Sales\Model\OrderFactory                    $orderFactory
     * @param \Magento\Framework\UrlInterface                      $urlBuilder
     * @param \Magento\Framework\Model\Context                     $context
     * @param \Magento\Framework\Registry                          $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory    $extensionFactory
     * @param \Magento\Framework\Api\AttributeValueFactory         $customAttributeFactory
     * @param \Magento\Payment\Helper\Data                         $paymentData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface   $scopeConfig
     * @param \Magento\Payment\Model\Method\Logger                 $logger
     * @param \Magento\Framework\Module\ModuleListInterface        $moduleList
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     * @param \Magento\Checkout\Model\Session                      $checkoutSession
     * @param \Magento\Sales\Model\OrderFactory                    $orderFactory
     * @param \MercadoPago\Core\Model\Core                         $coreModel
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \MercadoPago\Core\Helper\Data $helperData,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \MercadoPago\Core\Model\Core $coreModel,
        \Magento\Framework\App\RequestInterface $request)
    {
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

        $this->_helperData = $helperData;
        $this->_coreModel = $coreModel;
        $this->_checkoutSession = $checkoutSession;
        $this->_customerSession = $customerSession;
        $this->_orderFactory = $orderFactory;
        $this->_urlBuilder = $urlBuilder;
        $this->_request = $request;

    }

    /**
     * {inheritdoc}
     */
    public function postRequest(DataObject $request, ConfigInterface $config)
    {
        return '';
    }

    /**
     * Creates payment
     *
     * @param string $paymentAction
     * @param object $stateObject
     *
     * @return bool
     * @throws \Exception
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function initialize($paymentAction, $stateObject)
    {
        if ($this->getInfoInstance()->getAdditionalInformation('token') == "") {
            throw new \Exception(__('Verify the form data or wait until the validation of the payment data'));
        }

        $infoInstance = $this->getInfoInstance();

        if (!empty($infoInstance->getAdditionalInformation('second_card_token'))) {
            $usingSecondCardInfo['first_card']['amount'] = $infoInstance->getAdditionalInformation('first_card_amount');
            $usingSecondCardInfo['first_card']['installments'] = $infoInstance->getAdditionalInformation('installments');
            $usingSecondCardInfo['first_card']['payment_method_id'] = $infoInstance->getAdditionalInformation('payment_method');
            $usingSecondCardInfo['first_card']['token'] = $infoInstance->getAdditionalInformation('token');

            $usingSecondCardInfo['second_card']['amount'] = $infoInstance->getAdditionalInformation('second_card_amount');
            $usingSecondCardInfo['second_card']['installments'] = $infoInstance->getAdditionalInformation('second_card_installments');
            $usingSecondCardInfo['second_card']['payment_method_id'] = $infoInstance->getAdditionalInformation('second_card_payment_method_id');
            $usingSecondCardInfo['second_card']['token'] = $infoInstance->getAdditionalInformation('second_card_token');

            $responseFirstCard = $this->preparePostPayment($usingSecondCardInfo['first_card']);
            $this->_helperData->log("First payment response", self::LOG_NAME, $responseFirstCard);

            if (isset($responseFirstCard) && ($responseFirstCard['response']['status'] == 'approved')) {
                $paymentFirstCard = $responseFirstCard['response'];

                $responseSecondCard = $this->preparePostPayment($usingSecondCardInfo['second_card']);
                $this->_helperData->log("Second payment response", self::LOG_NAME, $responseSecondCard);

                if (isset($responseSecondCard) && ($responseSecondCard['response']['status'] == 'approved')) {
                    $paymentSecondCard = $responseSecondCard['response'];
                    $additionalInfo = [
                        'status'                       => $paymentFirstCard['status'] . ' | ' . $paymentSecondCard['status'],
                        'payment_id_detail'            => $paymentFirstCard['id'] . ' | ' . $paymentSecondCard['id'],
                        'status_detail'                => $paymentFirstCard['status_detail'] . ' | ' . $paymentSecondCard['status_detail'],
                        'installments'                 => $paymentFirstCard['installments'] . ' | ' . $paymentSecondCard['installments'],
                        'payment_method'               => $paymentFirstCard['payment_method_id'] . ' | ' . $paymentSecondCard['payment_method_id'],
                        'first_payment_id'             => $paymentFirstCard['id'] . ' | ' . $paymentSecondCard['id'],
                        'first_payment_status'         => $paymentFirstCard['status'] . ' | ' . $paymentSecondCard['status'],
                        'first_payment_status_detail'  => $paymentFirstCard['status_detail'] . ' | ' . $paymentSecondCard['status_detail'],
                        'total_paid_amount'            => $paymentFirstCard['transaction_details']['total_paid_amount'] . '|' . $paymentSecondCard['transaction_details']['total_paid_amount'],
                        'transaction_amount'           => $paymentFirstCard['transaction_amount'] . '|' . $paymentSecondCard['transaction_amount'],
                        'payer_identification_type'    => $paymentFirstCard['payer']['identification']['type']. '|' . $paymentSecondCard['payer']['identification']['type'],
                        'payer_identification_number'  => $paymentFirstCard['payer']['identification']['number'] . '|' . $paymentSecondCard['payer']['identification']['number']
                    ];

                    foreach ($additionalInfo as $infoKey => $infoValue) {
                        $infoInstance->setAdditionalInformation($infoKey, $infoValue);
                    };

                    return true;
                } else {
                    //second card payment failed, refund for first card
                    $accessToken = $this->_scopeConfig->getValue(\MercadoPago\Core\Helper\Data::XML_PATH_ACCESS_TOKEN, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
                    $mp = $this->_helperData->getApiInstance($accessToken);
                    $id = $paymentFirstCard['id'];
                    $refundResponse = $mp->post("/v1/payments/$id/refunds?access_token=$accessToken", null);
                    $this->_helperData->log("Second payment refund response", self::LOG_NAME, $refundResponse);

                    return false;
                }
            } else {
                return false;
            }


        } else {

            $response = $this->preparePostPayment();

            if ($response) {
                $payment = $response['response'];
                //set status
                $infoInstance->setAdditionalInformation('status', $payment['status']);
                $infoInstance->setAdditionalInformation('payment_id_detail', $payment['id']);
                $infoInstance->setAdditionalInformation('status_detail', $payment['status_detail']);
                $infoInstance->setAdditionalInformation('payer_identification_type', $payment['payer']['identification']['type']);
                $infoInstance->setAdditionalInformation('payer_identification_number', $payment['payer']['identification']['number']);
                return true;
            }
        }

        return false;


//        if ($this->getInfoInstance()->getAdditionalInformation('token') == "") {
//            throw new \Exception(__('Verify the form data or wait until the validation of the payment data'));
//        }
//
//        $response = $this->preparePostPayment();
//
//        if ($response) {
//            $payment = $response['response'];
//            $this->_helperData->log("Payment response", self::LOG_NAME, $payment);
//            //set status
//            $this->getInfoInstance()->setAdditionalInformation('status', $payment['status']);
//            $this->getInfoInstance()->setAdditionalInformation('status_detail', $payment['status_detail']);
//            $this->getInfoInstance()->setAdditionalInformation('payment_id_detail', $payment['id']);
//
//            return true;
//        }
//
//        return false;
    }

    /**
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function validate()
    {
        \Magento\Payment\Model\Method\AbstractMethod::validate();

        return $this;
    }

    /**
     * Assign corresponding data
     *
     * @param \Magento\Framework\DataObject|mixed $data
     *
     * @return $this
     * @throws \MercadoPago\Core\Model\Api\V1\Exception
     */
    public function assignData(\Magento\Framework\DataObject $data)
    {
        // route /checkout/onepage/savePayment
        if (!($data instanceof \Magento\Framework\DataObject)) {
            $data = new \Magento\Framework\DataObject($data);
        }

        $infoForm = $data->getData('additional_data');
        //$infoForm = $infoForm['mercadopago_custom'];
        if (isset($infoForm['one_click_pay']) && $infoForm['one_click_pay'] == 1) {
            $infoForm = $this->cleanFieldsOcp($infoForm);
        }

        if (empty($infoForm['token'])) {
            $e = "";
            $exception = new \MercadoPago\Core\Model\Api\V1\Exception(new \Magento\Framework\Phrase($e), $this->_scopeConfig);
            $e = $exception->getUserMessage();
            $exception->setPhrase(new \Magento\Framework\Phrase($e));
            throw $exception;
        }

        $this->_helperData->log("info form", self::LOG_NAME, $infoForm);
        $info = $this->getInfoInstance();
        $info->setAdditionalInformation($infoForm);
        $info->setAdditionalInformation('payment_type_id', "credit_card");
        if (!empty($infoForm['card_expiration_month']) && !empty($infoForm['card_expiration_year'])) {
            $info->setAdditionalInformation('expiration_date', $infoForm['card_expiration_month'] . "/" . $infoForm['card_expiration_year']);
        }
        $info->setAdditionalInformation('payment_method', $infoForm['payment_method_id']);
        $info->setAdditionalInformation('cardholderName', $infoForm['card_holder_name']);

        return $this;
    }

    /**
     * Fill preference with patment data
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \MercadoPago\Core\Model\Api\V1\Exception
     */
    public function preparePostPayment($usingSecondCardInfo = null)
    {
        $this->_helperData->log("Credit Card -> init prepare post payment", self::LOG_NAME);

        $quote = $this->_getQuote();
        $order = $this->getInfoInstance()->getOrder();
        $payment = $order->getPayment();
        $paymentInfo = $this->getPaymentInfo($payment);

        if (isset($usingSecondCardInfo)) {
            $paymentInfo['transaction_amount'] = $usingSecondCardInfo ['amount'];
        }

        $preference = $this->_coreModel->makeDefaultPreferencePaymentV1($paymentInfo, $quote, $order);

        if (isset($usingSecondCardInfo)) {
            $preference['installments'] = (int)$usingSecondCardInfo['installments'];
            $preference['payment_method_id'] = $usingSecondCardInfo['payment_method_id'];
            $preference['token'] = $usingSecondCardInfo['token'];
        } else {
            $preference['installments'] = (int)$payment->getAdditionalInformation("installments");
            $preference['payment_method_id'] = $payment->getAdditionalInformation("payment_method");
            $preference['token'] = $payment->getAdditionalInformation("token");
        }


        if ($payment->getAdditionalInformation("issuer_id") != "") {
            $preference['issuer_id'] = (int)$payment->getAdditionalInformation("issuer_id");
        }

        if ($payment->getAdditionalInformation("customer_id") != "") {
            $preference['payer']['id'] = $payment->getAdditionalInformation("customer_id");
        }

        $preference['binary_mode'] = $this->_scopeConfig->isSetFlag('payment/mercadopago_custom/binary_mode');
        $preference['statement_descriptor'] = $this->_scopeConfig->getValue('payment/mercadopago_custom/statement_descriptor');

        $this->_helperData->log("Credit Card -> PREFERENCE to POST /v1/payments", self::LOG_NAME, $preference);

        /* POST /v1/payments */
        $response = $this->_coreModel->postPaymentV1($preference);

        return $response;
    }


    /**
     * Retrieves quote
     *
     * @return \Magento\Quote\Model\Quote
     */
    protected function _getQuote()
    {
        return $this->_checkoutSession->getQuote();
    }


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
    }

    /**
     * Return success page url
     *
     * @return string
     */
    public function getOrderPlaceRedirectUrl()
    {
        $url = 'mercadopago/checkout/page';
        return $this->_urlBuilder->getUrl($url, ['_secure' => true]);
    }

    /**
     * is payment method available?
     *
     * @param \Magento\Quote\Api\Data\CartInterface|null $quote
     *
     * @return bool
     */
    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        $parent = parent::isAvailable($quote);
        if (!$this->_accessToken) {
            $this->_accessToken = $this->_scopeConfig->getValue(\MercadoPago\Core\Helper\Data::XML_PATH_ACCESS_TOKEN, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        }
        if (!$this->_publicKey) {
            $this->_publicKey = $this->_scopeConfig->getValue(\MercadoPago\Core\Helper\Data::XML_PATH_PUBLIC_KEY, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        }
        $custom = (!empty($this->_publicKey) && !empty($this->_accessToken));
        if (!$parent || !$custom) {
            return false;
        }

        $debugMode = $this->_scopeConfig->getValue('payment/mercadopago/debug_mode', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $secure = $this->_request->isSecure();
        if (!$secure && !$debugMode) {
            return false;
        }

        return $this->_helperData->isValidAccessToken($this->_accessToken);
    }

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
    }

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
    }

    /**
     * Saves customer tokenized card to be used later by OCP
     *
     * @param $customer
     * @param $token
     * @param $payment
     *
     * @return array|bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function checkAndcreateCard($customer, $token, $payment)
    {
        $accessToken = $this->getConfigData('access_token');
        $mp = $this->_helperData->getApiInstance($accessToken);

        foreach ($customer['cards'] as $card) {


            if ($card['first_six_digits'] == $payment['card']['first_six_digits']
                && $card['last_four_digits'] == $payment['card']['last_four_digits']
                && $card['expiration_month'] == $payment['card']['expiration_month']
                && $card['expiration_year'] == $payment['card']['expiration_year']
            ) {
                $this->_helperData->log("Card already exists", self::LOG_NAME, $card);

                return $card;
            }
        }
        $params = ["token" => $token];
        if (isset($payment['issuer_id'])) {
            $params['issuer_id'] = (int)$payment['issuer_id'];
        }
        if (isset($payment['payment_method_id'])) {
            $params['payment_method_id'] = $payment['payment_method_id'];
        }
        $card = $mp->post("/v1/customers/" . $customer['id'] . "/cards", $params);

        $this->_helperData->log("Response create card", self::LOG_NAME, $card);

        if ($card['status'] == 201) {
            return $card['response'];
        }

        return false;
    }

    /**
     * Saves to be used later by OCP
     *
     * @param $email
     *
     * @return bool|array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getOrCreateCustomer($email)
    {
        if (empty($email)) {
            return false;
        }
        //get access_token
        if (!$this->_accessToken) {
            $this->_accessToken = $this->_scopeConfig->getValue(\MercadoPago\Core\Helper\Data::XML_PATH_ACCESS_TOKEN, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        }

        $mp = $this->_helperData->getApiInstance($this->_accessToken);

        $customer = $mp->get("/v1/customers/search", ["email" => $email]);

        $this->_helperData->log("Response search customer", self::LOG_NAME, $customer);

        if ($customer['status'] == 200) {

            if ($customer['response']['paging']['total'] > 0) {
                return $customer['response']['results'][0];
            } else {
                $this->_helperData->log("Customer not found: " . $email, self::LOG_NAME);

                $customer = $mp->post("/v1/customers", ["email" => $email]);

                $this->_helperData->log("Response create customer", self::LOG_NAME, $customer);

                if ($customer['status'] == 201) {
                    return $customer['response'];
                } else {
                    return false;
                }
            }
        } else {
            return false;
        }
    }

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
    }

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

        if ($payment->getAdditionalInformation("coupon_code") != "") {
            $payment_info['coupon_code'] = $payment->getAdditionalInformation("coupon_code");
        }

        if ($payment->getAdditionalInformation("doc_number") != "") {
            $payment_info['identification_type'] = $payment->getAdditionalInformation("doc_type");
            $payment_info['identification_number'] = $payment->getAdditionalInformation("doc_number");
        }

        return $payment_info;
    }

}
