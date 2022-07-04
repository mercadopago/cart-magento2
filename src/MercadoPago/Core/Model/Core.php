<?php

namespace MercadoPago\Core\Model;

use Magento\Catalog\Helper\Image;
use Magento\Customer\Model\Session;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\DB\TransactionFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Magento\Payment\Model\Method\Logger;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\OrderFactory;
use Magento\Store\Model\StoreManagerInterface;
use MercadoPago\Core\Block\Adminhtml\System\Config\Version;
use MercadoPago\Core\Helper\ConfigData;
use MercadoPago\Core\Helper\Data;
use MercadoPago\Core\Helper\Message\MessageInterface;
use MercadoPago\Core\Helper\Round;
use MercadoPago\Core\Helper\SponsorId;
use MercadoPago\Core\Lib\Api;

/**
 * Core Model of MP plugin, used by all payment methods
 *
 * Class Core
 *
 * @package MercadoPago\Core\Model
 *
 * @codeCoverageIgnore
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
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var Data
     */
    protected $_coreHelper;

    /**
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var OrderFactory
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
     * @var MessageInterface
     */
    protected $_statusMessage;

    /**
     * @var MessageInterface
     */
    protected $_statusDetailMessage;

    /**
     * @var TransactionFactory
     */
    protected $_transactionFactory;

    /**
     * @var InvoiceSender
     */
    protected $_invoiceSender;

    /**
     * @var OrderSender
     */
    protected $_orderSender;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * @var Session
     */
    protected $_customerSession;

    /**
     * @var UrlInterface
     */
    protected $_urlBuilder;

    /**
     * @var Image
     */
    protected $_helperImage;

    /**
     * @var ProductMetadataInterface
     */
    protected $_productMetaData;

    /**
     * @var Version
     */
    protected $_version;

    /**
     * Core constructor.
     *
     * @param StoreManagerInterface $storeManager
     * @param Data $coreHelper
     * @param OrderFactory $orderFactory
     * @param MessageInterface $statusMessage
     * @param MessageInterface $statusDetailMessage
     * @param Context $context
     * @param Registry $registry
     * @param ExtensionAttributesFactory $extensionFactory
     * @param AttributeValueFactory $customAttributeFactory
     * @param Logger $logger
     * @param \Magento\Payment\Helper\Data $paymentData
     * @param ScopeConfigInterface $scopeConfig
     * @param TransactionFactory $transactionFactory
     * @param InvoiceSender $invoiceSender
     * @param OrderSender $orderSender
     * @param Session $customerSession
     * @param UrlInterface $urlBuilder
     * @param Image $helperImage
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param Version $version
     * @param ProductMetadataInterface $productMetadata
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        Data $coreHelper,
        OrderFactory $orderFactory,
        MessageInterface $statusMessage,
        MessageInterface $statusDetailMessage,
        Context $context,
        Registry $registry,
        ExtensionAttributesFactory $extensionFactory,
        AttributeValueFactory $customAttributeFactory,
        Logger $logger,
        \Magento\Payment\Helper\Data $paymentData,
        ScopeConfigInterface $scopeConfig,
        TransactionFactory $transactionFactory,
        InvoiceSender $invoiceSender,
        OrderSender $orderSender,
        Session $customerSession,
        UrlInterface $urlBuilder,
        Image $helperImage,
        \Magento\Checkout\Model\Session $checkoutSession,
        Version $version,
        ProductMetadataInterface $productMetadata
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
     * @return Quote
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    protected function _getQuote()
    {
        return $this->_checkoutSession->getQuote();
    }

    /**
     * Retrieves Order
     *
     * @param integer $incrementId
     * @return Order
     */
    public function _getOrder($incrementId)
    {
        return $this->_orderFactory->create()->loadByIncrementId($incrementId);
    }

    /**
     * Return array with data of payment of order loaded with order_id param
     *
     * @param $order_id
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
     * @return array
     */
    public function getMessageByStatus($status, $status_detail)
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
            $message['message'] = __($this->_statusDetailMessage->getMessage($status_detail));
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
     * @return array
     */
    protected function getCustomerInfo($customer, $order)
    {
        $email = $customer->getEmail();
        $email = is_string($email) ? htmlentities($email) : '';
        if ($email == "") {
            $email = $order['customer_email'];
        }

        $first_name = $customer->getFirstname();
        $first_name = is_string($first_name) ? htmlentities($first_name) : '';
        if ($first_name == "") {
            $first_name = $order->getBillingAddress()->getFirstname();
        }

        $last_name = $customer->getLastname();
        $last_name = is_string($last_name) ? htmlentities($last_name) : '';
        if ($last_name == "") {
            $last_name = $order->getBillingAddress()->getLastname();
        }

        return ['email' => $email, 'first_name' => $first_name, 'last_name' => $last_name];
    }

    /**
     * Return info about items of order
     *
     * @param $order
     * @param $quote
     * @return array
     */
    protected function getItemsInfo($order, $quote)
    {
        $dataItems = [];
        foreach ($order->getAllVisibleItems() as $item) {
            $product = $item->getProduct();
            $image = $this->_helperImage->init($product, 'product_thumbnail_image');

            $dataItems[] = [
                "id"          => $item->getSku(),
                "title"       => $product->getName(),
                "description" => $product->getName(),
                "picture_url" => $image->getUrl(),
                "quantity"    => Round::roundInteger($item->getQtyOrdered()),
                "unit_price"  => Round::roundWithSiteId($item->getPrice(), $this->getSiteId()),
                "category_id" => $this->_scopeConfig->getValue(
                    \MercadoPago\Core\Helper\ConfigData::PATH_ADVANCED_CATEGORY,
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                )
            ];
        }

        $discount = $this->getDiscountAmount($quote);
        if ($discount != 0) {
            $dataItems[] = [
                "id"          => __('Discount'),
                "title"       => __('Discount'),
                "description" => __('Discount'),
                "quantity"    => 1,
                "unit_price"  => Round::roundWithSiteId($discount, $this->getSiteId())
            ];
        }

        return $dataItems;
    }

    /**
     * @param Quote $quote
     * @return float
     */
    protected function getDiscountAmount(Quote $quote)
    {
        return ($quote->getSubtotalWithDiscount() - $quote->getBaseSubtotal());
    } //end processDiscount()

    /**
     * Return info of a coupon applied
     *
     * @param $coupon
     * @param $coupon_code
     * @return array
     */
    protected function getCouponInfo($coupon, $coupon_code)
    {
        $infoCoupon = [];
        $amount     = (float)$coupon['response']['coupon_amount'];

        $site_id = strtoupper($this->_scopeConfig->getValue(
            \MercadoPago\Core\Helper\ConfigData::PATH_SITE_ID,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        ));

        if ($site_id == "MCO" || $site_id == "MLC") {
            $amount = round($amount);
        }

        $infoCoupon['coupon_amount'] = $amount;
        $infoCoupon['coupon_code']   = $coupon_code;
        $infoCoupon['campaign_id']   = $coupon['response']['id'];

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
     * @param array $paymentInfo
     * @param null $quote
     * @param null $order
     * @return array
     * @throws NoSuchEntityException
     * @throws LocalizedException
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

        $customer        = $this->_customerSession->getCustomer();
        $billing_address = $quote->getBillingAddress()->getData();
        $customerInfo    = $this->getCustomerInfo($customer, $order);

        $preference = [];

        $notification_params = [
            '_query' => [
                'source_news' => 'webhooks'
            ]
        ];

        $notification_url = $this->_urlBuilder->getUrl('mercadopago/notifications/custom', $notification_params);
        if (isset($notification_url) && !strrpos($notification_url, 'localhost')) {
            $preference['notification_url'] = $notification_url;
        }

        $preference['description'] = __(
            "Order # %1 in store %2",
            $order->getIncrementId(),
            $this->_storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_LINK)
        );

        $preference['transaction_amount'] = $this->getAmount();
        $preference['external_reference'] = $order->getIncrementId();
        $preference['payer']['email']     = $customerInfo['email'];

        if (!empty($paymentInfo['identification_type'])) {
            $preference['payer']['identification']['type']   = $paymentInfo['identification_type'];
            $preference['payer']['identification']['number'] = $paymentInfo['identification_number'];
        }

        $preference['additional_info']['items']               = $this->getItemsInfo($order, $quote);
        $preference['additional_info']['payer']['first_name'] = $customerInfo['first_name'];
        $preference['additional_info']['payer']['last_name']  = $customerInfo['last_name'];

        $preference['additional_info']['payer']['address'] = [
            "zip_code" => $billing_address['postcode'],
            "street_name" => $billing_address['street'] . " - " . $billing_address['city'] . " - " . $billing_address['country_id'],
            "street_number" => ''
        ];

        $preference['additional_info']['payer']['registration_date'] = date(
            'Y-m-d',
            $customer->getCreatedAtTimestamp()
        ) . "T" . date(
            'H:i:s',
            $customer->getCreatedAtTimestamp()
        );

        if ($order->canShip()) {
            $shipping = $order->getShippingAddress()->getData();

            $preference['additional_info']['shipments']['receiver_address'] = [
                "zip_code"      => $shipping['postcode'],
                "street_name"   => $shipping['street'] . " - " . $shipping['city'] . " - " . $shipping['country_id'],
                "street_number" => '',
                "floor"         => "-",
                "apartment"     => "-",
            ];

            $preference['additional_info']['payer']['phone'] = [
                "area_code" => "-",
                "number" => $shipping['telephone']
            ];
        }

        $this->_coreHelper->log("==> makeDefaultPreferencePaymentV1 -> preference", 'mercadopago-standard.log', $preference);
        $this->_coreHelper->log("==> makeDefaultPreferencePaymentV1", 'mercadopago-standard.log', $paymentInfo);

        $sponsorId = $this->getSponsorId();
        $this->_coreHelper->log("Sponsor_id", 'mercadopago-standard.log', $sponsorId);

        $test_mode = false;

        if (!empty($sponsorId) && strpos($customerInfo['email'], "@testuser.com") === false) {
            $this->_coreHelper->log("Sponsor_id identificado", 'mercadopago-custom.log', $sponsorId);
            $preference['sponsor_id'] = (int)$sponsorId;
            $test_mode = true;
        }

        $this->_version->afterLoad();

        $preference['metadata'] = [
            "platform"         => "BP1EF6QIC4P001KBGQ10",
            "platform_version" => $this->_productMetaData->getVersion(),
            "module_version"   => $this->_version->getValue(),
            "sponsor_id"       => $sponsorId,
            "test_mode"        => $test_mode,
            "site"             => $this->_scopeConfig->getValue(
                \MercadoPago\Core\Helper\ConfigData::PATH_SITE_ID,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            )
        ];

        return $preference;
    }

    /**
     * Return response of api to a preference
     *
     * @param $preference
     * @return array
     * @throws LocalizedException
     */
    public function postPaymentV1($preference)
    {
        return $this->getMercadoPagoInstance()->post("/v1/payments", $preference);
    }

    /**
     * Get message error by response API
     *
     * @param $response
     * @return string
     */
    public function getMessageError($response)
    {
        $errors = \MercadoPago\Core\Helper\Response::PAYMENT_CREATION_ERRORS;

        //set default error
        $messageErrorToClient = $errors['NOT_IDENTIFIED'];

        if (isset($response['response']) &&
            isset($response['response']['cause']) &&
            count($response['response']['cause']) > 0
        ) {

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
     * @return array
     * @throws LocalizedException
     */
    public function getPaymentV1($payment_id)
    {
        return $this->getMercadoPagoInstance()->get("/v1/payments/" . $payment_id);
    }

    /**
     * @return array
     * @throws LocalizedException
     */
    public function getPaymentMethods()
    {
        $this->getMercadoPagoInstance();
        return $this->_coreHelper->getMercadoPagoPaymentMethods($this->_accessToken);
    }

    /**
     * @return array
     * @throws LocalizedException
     */
    public function getIdentificationTypes()
    {
        return $this->getMercadoPagoInstance()->get("/v1/identification_types");
    }

    /**
     * @return mixed|string
     * @throws LocalizedException
     * @throws NoSuchEntityException
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
     * @param  Quote|null $quote
     * @return float
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getAmount($quote = null)
    {
        if (!$quote) {
            $quote = $this->_getQuote();
        }

        $total = $quote->getBaseGrandTotal();

        return Round::roundWithSiteId($total, $this->getSiteId());
    }

    /**
     * Check if an applied coupon is valid
     *
     * @param $coupon_id
     * @param $email
     * @return array
     * @throws LocalizedException
     */
    public function validCoupon($coupon_id, $email = null)
    {
        $transaction_amount = $this->getAmount();
        $payer_email = $this->getEmailCustomer();
        $coupon_code = $coupon_id;

        if ($payer_email == "") {
            $payer_email = $email;
        }

        $details_discount = $this->getMercadoPagoInstance()->check_discount_campaigns(
            $transaction_amount,
            $payer_email,
            $coupon_code
        );

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
     * @return array
     * @throws LocalizedException
     */
    public function getMerchantOrder($merchant_order_id)
    {
        return $this->getMercadoPagoInstance()->get("/merchant_orders/" . $merchant_order_id);
    }

    /**
     * @param $payment_id
     * @return array
     * @throws LocalizedException
     */
    public function getPayment($payment_id)
    {
        return $this->getMercadoPagoInstance()->get("/v1/payments/" . $payment_id);
    }

    /**
     * @return array
     * @throws LocalizedException
     */
    public function getUserMe()
    {
        return $this->getMercadoPagoInstance()->get('/users/me');
    }

    /**
     * @return Api
     * @throws LocalizedException
     */
    protected function getMercadoPagoInstance()
    {
        if (!$this->_accessToken) {
            $this->_accessToken = $this->_scopeConfig->getValue(\MercadoPago\Core\Helper\ConfigData::PATH_ACCESS_TOKEN, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        }

        return $this->_coreHelper->getApiInstance($this->_accessToken);
    }

    /**
     * @return int|null
     */
    protected function getSponsorId()
    {
        $siteId = $this->_scopeConfig->getValue(
            \MercadoPago\Core\Helper\ConfigData::PATH_SITE_ID,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        return SponsorId::getSponsorId($siteId);
    } //end getSponsorId()

    /**
     * @return false|string|string[]
     */
    protected function getSiteId()
    {
        return mb_strtoupper($this->_scopeConfig->getValue(
            \MercadoPago\Core\Helper\ConfigData::PATH_SITE_ID,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        ));
    } //end getSiteId()
}
