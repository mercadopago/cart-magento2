<?php

namespace MercadoPago\Core\Model;

/**
 * Core Model of MP plugin, used by all payment methods
 *
 * Class Core
 *
 * @package MercadoPago\Core\Model
 */
class Core extends \Magento\Payment\Model\Method\AbstractMethod
{
    /**
     * @var string
     */
    protected $_code = 'mercadopago';

    /**
     * {@inheritdoc}
     */
    protected $_isGateway = true;

    /**
     * {@inheritdoc}
     */
    protected $_canOrder = true;
    /**
     * {@inheritdoc}
     */
    protected $_canAuthorize = true;
    /**
     * {@inheritdoc}
     */
    protected $_canCapture = true;

    /**
     * {@inheritdoc}
     */
    protected $_canCapturePartial = true;

    /**
     * {@inheritdoc}
     */
    protected $_canRefund = true;
    /**
     * {@inheritdoc}
     */
    protected $_canRefundInvoicePartial = true;

    /**
     * {@inheritdoc}
     */
    protected $_canVoid = true;

    /**
     * {@inheritdoc}
     */
    protected $_canUseInternal = true;

    /**
     * {@inheritdoc}
     */
    protected $_canUseCheckout = true;

    /**
     * {@inheritdoc}
     */
    protected $_canFetchTransactionInfo = true;

    /**
     * {@inheritdoc}
     */
    protected $_canReviewPayment = true;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \MercadoPago\Core\Helper\Data
     */
    protected $_coreHelper;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $_orderFactory;

    /**
     * @var
     */
    protected $_accessToken;
    /**
     * @var
     */
    protected $_clientId;
    /**
     * @var
     */
    protected $_clientSecret;

    /**
     * @var \MercadoPago\Core\Helper\Message\MessageInterface
     */
    protected $_statusMessage;
    /**
     * @var \MercadoPago\Core\Helper\Message\MessageInterface
     */
    protected $_statusDetailMessage;
    /**
     * @var \Magento\Framework\DB\TransactionFactory
     */
    protected $_transactionFactory;
    /**
     * @var \Magento\Sales\Model\Order\Email\Sender\InvoiceSender
     */
    protected $_invoiceSender;
    /**
     * @var \Magento\Sales\Model\Order\Email\Sender\OrderSender
     */
    protected $_orderSender;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;
    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $_urlBuilder;
    /**
     * @var \Magento\Catalog\Helper\Image
     */
    protected $_helperImage;

    /**
     * @var \Magento\Framework\App\ProductMetadataInterface
     */
    protected $_productMetaData;

    /**
     * @var \MercadoPago\Core\Block\Adminhtml\System\Config\Version
     */
    protected $_version;

    /**
     * Core constructor.
     *
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \MercadoPago\Core\Helper\Data $coreHelper
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \MercadoPago\Core\Helper\Message\MessageInterface $statusMessage
     * @param \MercadoPago\Core\Helper\Message\MessageInterface $statusDetailMessage
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory
     * @param \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory
     * @param \Magento\Payment\Model\Method\Logger $logger
     * @param \Magento\Payment\Helper\Data $paymentData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\DB\TransactionFactory $transactionFactory
     * @param \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender
     * @param \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\UrlInterface $urlBuilder
     * @param \Magento\Catalog\Helper\Image $helperImage
     * @param \Magento\Checkout\Model\Session $checkoutSession
     */
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \MercadoPago\Core\Helper\Data $coreHelper,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \MercadoPago\Core\Helper\Message\MessageInterface $statusMessage,
        \MercadoPago\Core\Helper\Message\MessageInterface $statusDetailMessage,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\DB\TransactionFactory $transactionFactory,
        \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Catalog\Helper\Image $helperImage,
        \Magento\Checkout\Model\Session $checkoutSession,
        \MercadoPago\Core\Block\Adminhtml\System\Config\Version $version,
        \Magento\Framework\App\ProductMetadataInterface $productMetadata
    ) {
        parent::__construct($context, $registry, $extensionFactory, $customAttributeFactory, $paymentData, $scopeConfig, $logger, null, null, []);
        $this->_storeManager = $storeManager;
        $this->_coreHelper = $coreHelper;
        $this->_orderFactory = $orderFactory;
        $this->_statusMessage = $statusMessage;
        $this->_statusDetailMessage = $statusDetailMessage;
        $this->_transactionFactory = $transactionFactory;
        $this->_invoiceSender = $invoiceSender;
        $this->_orderSender = $orderSender;
        $this->_customerSession = $customerSession;
        $this->_urlBuilder = $urlBuilder;
        $this->_helperImage = $helperImage;
        $this->_checkoutSession = $checkoutSession;
        $this->_productMetaData = $productMetadata;
        $this->_version = $version;
    }

    /**
     * Retrieves Quote
     *
     * @param integer $quoteId
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
     * @param integer $incrementId
     *
     * @return \Magento\Sales\Model\Order
     */
    public function _getOrder($incrementId)
    {
        return $this->_orderFactory->create()->loadByIncrementId($incrementId);
    }

    /**
     * Return array with data of payment of order loaded with order_id param
     *
     * @param $order_id
     *
     * @return array
     */
    // @REFACTOR
    public function getInfoPaymentByOrder($order_id)
    {
        $order = $this->_getOrder($order_id);
        $payment = $order->getPayment();
        $info_payments = [];
        $fields = [
            ["field" => "cardholderName", "title" => "Card Holder Name: %1"],
            ["field" => "trunc_card", "title" => "Card Number: %1"],
            ["field" => "payment_method", "title" => "Payment Method: %1"],
            ["field" => "expiration_date", "title" => "Expiration Date: %1"],
            ["field" => "installments", "title" => "Installments: %1"],
            ["field" => "statement_descriptor", "title" => "Statement Descriptor: %1"],
            ["field" => "payment_id", "title" => "Payment id (Mercado Pago): %1"],
            ["field" => "status", "title" => "Payment Status: %1"],
            ["field" => "status_detail", "title" => "Payment Detail: %1"],
            ["field" => "activation_uri", "title" => "Generate Ticket"],
            ["field" => "payment_id_detail", "title" => "Mercado Pago Payment Id: %1"],
            ["field" => "id", "title" => "Collection Id: %1"],
        ];

        foreach ($fields as $field) {
            if ($payment->getAdditionalInformation($field['field']) != "") {
                $text = __($field['title'], $payment->getAdditionalInformation($field['field']));
                $info_payments[$field['field']] = [
                    "text" => $text,
                    "value" => $payment->getAdditionalInformation($field['field'])
                ];
            }
        }

        if ($payment->getAdditionalInformation('payer_identification_type') != "") {
            $text = __($payment->getAdditionalInformation('payer_identification_type'));
            $info_payments[$payment->getAdditionalInformation('payer_identification_type')] = [
                "text" => $text . ': ' . $payment->getAdditionalInformation('payer_identification_number')
            ];
        }

        return $info_payments;
    }

    /**
     * Check if status is final in case of multiple card payment
     *
     * @param $status
     *
     * @return string
     */
    protected function validStatusTwoPayments($status)
    {
        $array_status = explode(" | ", $status);
        $status_verif = true;
        $status_final = "";
        foreach ($array_status as $status) {
            if ($status_final == "") {
                $status_final = $status;
            } else {
                if ($status_final != $status) {
                    $status_verif = false;
                }
            }
        }

        if ($status_verif === false) {
            $status_final = "other";
        }

        return $status_final;
    }

    /**
     * Return array message depending on status
     *
     * @param $status
     * @param $status_detail
     * @param $payment_method
     * @param $installment
     * @param $amount
     *
     * @return array
     */
    public function getMessageByStatus($status, $status_detail, $payment_method, $installment, $amount)
    {
        $status = $this->validStatusTwoPayments($status);
        $status_detail = $this->validStatusTwoPayments($status_detail);

        $message = [
            "title" => "",
            "message" => ""
        ];

        $rawMessage = $this->_statusMessage->getMessage($status);
        $message['title'] = __($rawMessage['title']);

        if ($status == 'rejected') {
            if ($status_detail == 'cc_rejected_invalid_installments') {
                $message['message'] = __($this->_statusDetailMessage->getMessage($status_detail), strtoupper($payment_method), $installment);
            } elseif ($status_detail == 'cc_rejected_call_for_authorize') {
                $message['message'] = __($this->_statusDetailMessage->getMessage($status_detail), strtoupper($payment_method), $amount);
            } else {
                $message['message'] = __($this->_statusDetailMessage->getMessage($status_detail), strtoupper($payment_method));
            }
        } else {
            $message['message'] = __($rawMessage['message']);
        }

        return $message;
    }

    /**
     * Return array with info of customer
     *
     * @param $customer
     * @param $order
     *
     * @return array
     */
    protected function getCustomerInfo($customer, $order)
    {
        $email = htmlentities($customer->getEmail());
        if ($email == "") {
            $email = $order['customer_email'];
        }

        $first_name = htmlentities($customer->getFirstname());
        if ($first_name == "") {
            $first_name = $order->getBillingAddress()->getFirstname();
        }

        $last_name = htmlentities($customer->getLastname());
        if ($last_name == "") {
            $last_name = $order->getBillingAddress()->getLastname();
        }

        return ['email' => $email, 'first_name' => $first_name, 'last_name' => $last_name];
    }

    /**
     * Return info about items of order
     *
     * @param $order
     *
     * @return array
     */
    protected function getItemsInfo($order)
    {
        $dataItems = [];
        foreach ($order->getAllVisibleItems() as $item) {
            $product = $item->getProduct();
            $image = $this->_helperImage->init($product, 'image');

            $dataItems[] = [
                "id" => $item->getSku(),
                "title" => $product->getName(),
                "description" => $product->getName(),
                "picture_url" => $image->getUrl(),
                "category_id" => $this->_scopeConfig->getValue(\MercadoPago\Core\Helper\ConfigData::PATH_ADVANCED_CATEGORY, \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                "quantity" => (int)number_format($item->getQtyOrdered(), 0, '.', ''),
                "unit_price" => (float)number_format($item->getPrice(), 2, '.', '')
            ];
        }

        /* verify discount and add it like an item */
        $discount = $this->getDiscount();
        if ($discount != 0) {
            $dataItems[] = [
                "title" => "Discount by the Store",
                "description" => "Discount by the Store",
                "quantity" => 1,
                "unit_price" => (float)number_format($discount, 2, '.', '')
            ];
        }

        return $dataItems;
    }

    /**
     * Return info of a coupon applied
     *
     * @param $coupon
     * @param $coupon_code
     *
     * @return array
     */
    protected function getCouponInfo($coupon, $coupon_code)
    {
        $infoCoupon = [];
        $amount = (float)$coupon['response']['coupon_amount'];
        $site_id = strtoupper($this->_scopeConfig->getValue(\MercadoPago\Core\Helper\ConfigData::PATH_SITE_ID, \Magento\Store\Model\ScopeInterface::SCOPE_STORE));
        if ($site_id == "MCO" || $site_id == "MLC") {
            $amount = round($amount);
        }
        $infoCoupon['coupon_amount'] = $amount;
        $infoCoupon['coupon_code'] = $coupon_code;
        $infoCoupon['campaign_id'] = $coupon['response']['id'];
        if ($coupon['status'] == 200) {
            $this->_coreHelper->log("Coupon applied. API response 200.", 'mercadopago-custom.log');
        } else {
            $this->_coreHelper->log("Coupon invalid, not applied.", 'mercadopago-custom.log');
        }

        return $infoCoupon;
    }

    /**
     * Return array with preference data by default to custom method
     *
     * @param array $payment_info
     *
     * @return array
     */
    public function makeDefaultPreferencePaymentV1($paymentInfo = [], $quote = null, $order = null)
    {
        if (!$quote) {
            $quote = $this->_getQuote();
        }
        $orderId = $quote->getReservedOrderId();
        if (!$order) {
            $order = $this->_getOrder($orderId);
        }

        $customer = $this->_customerSession->getCustomer();

        $billing_address = $quote->getBillingAddress()->getData();
        $customerInfo = $this->getCustomerInfo($customer, $order);

        /* INIT PREFERENCE */
        $preference = [];

        // Check if notification URL contains localhost
        $notification_url = $this->_urlBuilder->getUrl('mercadopago/notifications/custom');
        if (isset($notification_url) && !strrpos($notification_url, 'localhost')) {
            $preference['notification_url'] = $notification_url;
        }

        $preference['description'] = __("Order # %1 in store %2", $order->getIncrementId(), $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_LINK));

        $preference['transaction_amount'] = round((float)$this->getAmount(),2);

        $preference['external_reference'] = $order->getIncrementId();

        $preference['payer']['email'] = $customerInfo['email'];

        if (!empty($paymentInfo['identification_type'])) {
            $preference['payer']['identification']['type'] = $paymentInfo['identification_type'];
            $preference['payer']['identification']['number'] = $paymentInfo['identification_number'];
        }
        $preference['additional_info']['items'] = $this->getItemsInfo($order);

        $preference['additional_info']['payer']['first_name'] = $customerInfo['first_name'];
        $preference['additional_info']['payer']['last_name'] = $customerInfo['last_name'];

        $preference['additional_info']['payer']['address'] = [
            "zip_code" => $billing_address['postcode'],
            "street_name" => $billing_address['street'] . " - " . $billing_address['city'] . " - " . $billing_address['country_id'],
            "street_number" => ''
        ];

        $preference['additional_info']['payer']['registration_date'] = date('Y-m-d', $customer->getCreatedAtTimestamp()) . "T" . date('H:i:s', $customer->getCreatedAtTimestamp());

        if ($order->canShip()) {
            $shipping = $order->getShippingAddress()->getData();

            $preference['additional_info']['shipments']['receiver_address'] = [
                "zip_code" => $shipping['postcode'],
                "street_name" => $shipping['street'] . " - " . $shipping['city'] . " - " . $shipping['country_id'],
                "street_number" => '',
                "floor" => "-",
                "apartment" => "-",

            ];

            $preference['additional_info']['payer']['phone'] = [
                "area_code" => "0",
                "number" => $shipping['telephone']
            ];
        }

        $this->_coreHelper->log("==> makeDefaultPreferencePaymentV1 -> preference", 'mercadopago-standard.log', $preference);

        $this->_coreHelper->log("==> makeDefaultPreferencePaymentV1", 'mercadopago-standard.log', $paymentInfo);

        $sponsorId = $this->_scopeConfig->getValue('payment/mercadopago/sponsor_id', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        $this->_coreHelper->log("Sponsor_id", 'mercadopago-standard.log', $sponsorId);

        $test_mode = false;
        if (!empty($sponsorId) && strpos($customerInfo['email'], "@testuser.com") === false) {
            $this->_coreHelper->log("Sponsor_id identificado", 'mercadopago-custom.log', $sponsorId);
            $preference['sponsor_id'] = (int)$sponsorId;
            $test_mode = true;
        }

        $this->_version->afterLoad();

        $preference['metadata'] = [
            "site" => $this->_scopeConfig->getValue(\MercadoPago\Core\Helper\ConfigData::PATH_ADVANCED_COUNTRY, \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
            "platform" => "Magento",
            "platform_version" => $this->_productMetaData->getVersion(),
            "module_version" => $this->_version->getValue(),
            "sponsor_id" => $sponsorId,
            "test_mode" => $test_mode,
        ];

        return $preference;
    }

    /**
     * Return response of api to a preference
     *
     * @param $preference
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function postPaymentV1($preference)
    {
        return $this->getMercadoPagoInstance()->post("/v1/payments", $preference);
    }

    /**
     * Get message error by response API
     *
     * @param $response
     *
     * @return string
     */
    public function getMessageError($response)
    {
        $errors = \MercadoPago\Core\Helper\Response::PAYMENT_CREATION_ERRORS;

        //set default error
        $messageErrorToClient = $errors['NOT_IDENTIFIED'];

        if (isset($response['response']) &&
            isset($response['response']['cause']) &&
            count($response['response']['cause']) > 0) {

            // get first error
            $cause = $response['response']['cause'][0];

            if (isset($errors[$cause['code']])) {

                //if exist get message error
                $messageErrorToClient = $errors[$cause['code']];
            }
        }

        return $messageErrorToClient;
    }

    /**
     *  Return info of payment returned by MP api
     *
     * @param $payment_id
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getPaymentV1($payment_id)
    {
        return $this->getMercadoPagoInstance()->get("/v1/payments/" . $payment_id);
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getPaymentMethods()
    {
        return $this->getMercadoPagoInstance()->get("/v1/payment_methods");
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getIdentificationTypes()
    {
        return $this->getMercadoPagoInstance()->get("/v1/identification_types");
    }

    /**
     * @return mixed|string
     */
    public function getEmailCustomer()
    {
        $customer = $this->_customerSession->getCustomer();
        $email = $customer->getEmail();
        if (empty($email)) {
            $quote = $this->_getQuote();
            $email = $quote->getBillingAddress()->getEmail();
        }

        return $email;
    }

    /**
     * @return float
     */
    public function getAmount($quote = null)
    {
        if (!$quote) {
            $quote = $this->_getQuote();
        }

        $total = $quote->getBaseGrandTotal();

        return (float)$total;
    }

    /**
     * Check if an applied coupon is valid
     *
     * @param $coupon_id
     * @param $email
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function validCoupon($coupon_id, $email = null)
    {
        $transaction_amount = $this->getAmount();
        $payer_email = $this->getEmailCustomer();
        $coupon_code = $coupon_id;

        if ($payer_email == "") {
            $payer_email = $email;
        }

        $details_discount = $this->getMercadoPagoInstance()->check_discount_campaigns($transaction_amount, $payer_email, $coupon_code);

        //add value on return api discount
        $details_discount['response']['transaction_amount'] = $transaction_amount;
        $details_discount['response']['params'] = [
            "transaction_amount" => $transaction_amount,
            "payer_email" => $payer_email,
            "coupon_code" => $coupon_code
        ];

        return $details_discount;
    }

    /**
     * Return info of order returned by MP api
     *
     * @param $merchant_order_id
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getMerchantOrder($merchant_order_id)
    {
        return $this->getMercadoPagoInstance()->get("/merchant_orders/" . $merchant_order_id);
    }

    public function getPayment($payment_id)
    {
        return $this->getMercadoPagoInstance()->get("/v1/payments/" . $payment_id);
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getUserMe()
    {
        return $this->getMercadoPagoInstance()->get('/users/me');
    }

    /**
     * @return \MercadoPago\Core\Lib\Api
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getMercadoPagoInstance()
    {
        if (!$this->_accessToken) {
            $this->_accessToken = $this->_scopeConfig->getValue(\MercadoPago\Core\Helper\ConfigData::PATH_ACCESS_TOKEN, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        }
        return $this->_coreHelper->getApiInstance($this->_accessToken);
    }
}
