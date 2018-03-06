<?php

namespace MercadoPago\Core\Model\Standard;

/**
 * Class Payment
 *
 * @package MercadoPago\Core\Model\Standard
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Payment
    extends \Magento\Payment\Model\Method\AbstractMethod
{
    /**
     * Define payment method code
     */
    const CODE = 'mercadopago_standard';

    /**
     * define URL to go when an order is placed
     */
    const ACTION_URL = 'mercadopago/standard/pay';

    /**
     * {@inheritdoc}
     */
    protected $_code = self::CODE;

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
     * Availability option
     *
     * @var bool
     */
    protected $_canUseInternal = false;

    /**
     * {@inheritdoc}
     */
    protected $_canFetchTransactionInfo = true;

    /**
     * {@inheritdoc}
     */
    protected $_canReviewPayment = true;

    /**
     * @var \MercadoPago\Core\Helper\Data
     */
    protected $_helperData;

    /**
     * @var \Magento\Catalog\Helper\Image
     */
    protected $_helperImage;

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
     * @var string
     */
    protected $_infoBlockType = 'MercadoPago\Core\Block\Info';

    /**
     * @param \MercadoPago\Core\Helper\Data                                $helperData
     * @param \Magento\Catalog\Helper\Image                                $helperImage
     * @param \Magento\Checkout\Model\Session                              $checkoutSession
     * @param \Magento\Customer\Model\Session                              $customerSession
     * @param \Magento\Sales\Model\OrderFactory                            $orderFactory
     * @param \Magento\Framework\UrlInterface                              $urlBuilder
     * @param \Magento\Framework\Model\Context                             $context
     * @param \Magento\Framework\Registry                                  $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory            $extensionFactory
     * @param \Magento\Framework\Api\AttributeValueFactory                 $customAttributeFactory
     * @param \Magento\Payment\Helper\Data                                 $paymentData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface           $scopeConfig
     * @param \Magento\Payment\Model\Method\Logger                         $logger
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null           $resourceCollection
     * @param array                                                        $data
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \MercadoPago\Core\Helper\Data $helperData,
        \Magento\Catalog\Helper\Image $helperImage,
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
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    )
    {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data
        );

        $this->_helperData = $helperData;
        $this->_helperImage = $helperImage;

        $this->_checkoutSession = $checkoutSession;
        $this->_customerSession = $customerSession;
        $this->_orderFactory = $orderFactory;
        $this->_urlBuilder = $urlBuilder;
    }

    /**
     * Return array with data of payment in MP site
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function postPago()
    {
        $client_id = $this->_scopeConfig->getValue(\MercadoPago\Core\Helper\Data::XML_PATH_CLIENT_ID, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $client_secret = $this->_scopeConfig->getValue(\MercadoPago\Core\Helper\Data::XML_PATH_CLIENT_SECRET, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        $mp = $this->_helperData->getApiInstance($client_id, $client_secret);

        $pref = $this->makePreference();
        $this->_helperData->log("make array", 'mercadopago-standard.log', $pref);

        $response = $mp->create_preference($pref);
        $this->_helperData->log("create preference result", 'mercadopago-standard.log', $response);

        if ($response['status'] == 200 || $response['status'] == 201) {
            $payment = $response['response'];
            if ($this->_scopeConfig->getValue('payment/mercadopago_standard/sandbox_mode', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)) {
                $init_point = $payment['sandbox_init_point'];
            } else {
                $init_point = $payment['init_point'];
            }

            $array_assign = [
                "init_point"      => $init_point,
                "type_checkout"   => $this->getConfigData('type_checkout'),
                "iframe_width"    => $this->getConfigData('iframe_width'),
                "iframe_height"   => $this->getConfigData('iframe_height'),
                "banner_checkout" => $this->getConfigData('banner_checkout'),
                "status"          => 201
            ];

            $this->_helperData->log("Array preference ok", 'mercadopago-standard.log');
        } else {
            $array_assign = [
                "message" => __('An error has occurred. Please refresh the page.'),
                "json"    => json_encode($response),
                "status"  => 400
            ];

            $this->_helperData->log("Array preference error", 'mercadopago-standard.log');
        }

        return $array_assign;
    }

    /**
     * Return array with data to send to MP api
     *
     * @return array
     */
    public function makePreference()
    {
        $orderIncrementId = $this->_checkoutSession->getLastRealOrderId();
        $order = $this->_orderFactory->create()->loadByIncrementId($orderIncrementId);
        $customer = $this->_customerSession->getCustomer();
        $payment = $order->getPayment();
        $paramsShipment = new \Magento\Framework\DataObject();
        $paramsShipment->setParams([]);

        $this->_eventManager->dispatch(
            'mercadopago_standard_make_preference_before',
            ['params' => $paramsShipment, 'order' => $order]
        );

        $arr = [];
        $arr['external_reference'] = $orderIncrementId;
        $arr['items'] = $this->getItems($order);

        $this->_calculateDiscountAmount($arr['items'], $order);
        $this->_calculateBaseTaxAmount($arr['items'], $order);
        $total_item = $this->getTotalItems($arr['items']);
        $total_item += (float)$order->getBaseShippingAmount();
        $order_amount = (float)$order->getBaseGrandTotal();
        if (!$order_amount) {
            $order_amount = (float)$order->getBasePrice() + $order->getBaseShippingAmount();
        }

        if ($total_item > $order_amount || $total_item < $order_amount) {
            $diff_price = $order_amount - $total_item;
            $arr['items'][] = [
                "title"       => "Difference amount of the items with a total",
                "description" => "Difference amount of the items with a total",
                "category_id" => $this->_scopeConfig->getValue('payment/mercadopago/category_id', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                "quantity"    => 1,
                "unit_price"  => (float)$diff_price
            ];
            $this->_helperData->log("Total itens: " . $total_item, 'mercadopago-standard.log');
            $this->_helperData->log("Total order: " . $order_amount, 'mercadopago-standard.log');
            $this->_helperData->log("Difference add itens: " . $diff_price, 'mercadopago-standard.log');
        }
        if ($order->canShip()) {
            $shippingAddress = $order->getShippingAddress();
            $shipping = $shippingAddress->getData();

            $arr['payer']['phone'] = [
                "area_code" => "-",
                "number"    => $shipping['telephone']
            ];

            $arr['shipments'] = $this->_getParamShipment($paramsShipment, $order, $shippingAddress);
        }

        $billingAddress = $order->getBillingAddress()->getData();
        $arr['payer']['date_created'] = date('Y-m-d', $customer->getCreatedAtTimestamp()) . "T" . date('H:i:s', $customer->getCreatedAtTimestamp());
        if (!$customer->getId()) {
            $arr['payer']['email'] = htmlentities($billingAddress['email']);
            $arr['payer']['first_name'] = htmlentities($billingAddress['firstname']);
            $arr['payer']['last_name'] = htmlentities($billingAddress['lastname']);
        } else {
            $arr['payer']['email'] = htmlentities($customer->getEmail());
            $arr['payer']['first_name'] = htmlentities($customer->getFirstname());
            $arr['payer']['last_name'] = htmlentities($customer->getLastname());
        }

        if (isset($payment['additional_information']['doc_number']) && $payment['additional_information']['doc_number'] != "") {
            $arr['payer']['identification'] = [
                "type"   => "CPF",
                "number" => $payment['additional_information']['doc_number']
            ];
        }

        $arr['payer']['address'] = [
            "zip_code"      => $billingAddress['postcode'],
            "street_name"   => $billingAddress['street'] . " - " . $billingAddress['city'] . " - " . $billingAddress['country_id'],
            "street_number" => ""
        ];

        $url = $this->_helperData->getSuccessUrl();
        $arr['back_urls']['success'] = $this->_urlBuilder->getUrl($url);

        $typeCheckout = $this->_scopeConfig->getValue('payment/mercadopago_standard/type_checkout', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        if ($typeCheckout == 'redirect') {
            $arr['back_urls']['pending'] = $this->_urlBuilder->getUrl($url);
            if (!$this->_scopeConfig->getValue(\MercadoPago\Core\Helper\Data::XML_PATH_USE_SUCCESSPAGE_MP, \Magento\Store\Model\ScopeInterface::SCOPE_STORE)){
                $arr['back_urls']['failure'] = $this->_urlBuilder->getUrl('checkout/onepage/failure');
            }else{
                $arr['back_urls']['failure'] = $this->_urlBuilder->getUrl('mercadopago/standard/failure');
            }
        }

        $arr['notification_url'] = $this->_urlBuilder->getUrl("mercadopago/notifications/standard");

        $arr['payment_methods']['excluded_payment_methods'] = $this->getExcludedPaymentsMethods();
        $installments = $this->getConfigData('installments');
        $arr['payment_methods']['installments'] = (int)$installments;

        $auto_return = $this->getConfigData('auto_return');
        if ($auto_return == 1) {
            $arr['auto_return'] = "approved";
        }

        $sponsor_id = $this->_scopeConfig->getValue('payment/mercadopago/sponsor_id', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $this->_helperData->log("Sponsor_id", 'mercadopago-standard.log', $sponsor_id);
        if (!empty($sponsor_id)) {
            $this->_helperData->log("Sponsor_id identificado", 'mercadopago-standard.log', $sponsor_id);
            $arr['sponsor_id'] = (int)$sponsor_id;
        }

        return $arr;
    }

    protected function _getParamShipment($params, $order, $shippingAddress) {
        $paramsShipment = $params->getParams();
        if (empty($paramsShipment)) {
            $paramsShipment = $params->getData();
            $paramsShipment['cost'] = (float)$order->getBaseShippingAmount();
            $paramsShipment['mode'] = 'custom';
        }
        $paramsShipment['receiver_address'] = $this->getReceiverAddress($shippingAddress);
        return $paramsShipment;
    }

    /**
     * Return array with data of items of order
     *
     * @param $order
     *
     * @return array
     */
    protected function getItems($order)
    {
        $items = [];
        foreach ($order->getAllVisibleItems() as $item) {
            $product = $item->getProduct();
            $image = $this->_helperImage->init($product, 'image');

            $items[] = [
                "id"          => $item->getSku(),
                "title"       => $product->getName(),
                "description" => $product->getName(),
                "picture_url" => $image->getUrl(),
                "category_id" => $this->_scopeConfig->getValue('payment/mercadopago/category_id', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                "quantity"    => (int)number_format($item->getQtyOrdered(), 0, '.', ''),
                "unit_price"  => (float)number_format($item->getPrice(), 2, '.', '')
            ];
        }

        return $items;
    }

    /**
     * Calculate discount of magento site and set data in arr param
     *
     * @param $arr
     * @param $order
     */
    protected function _calculateDiscountAmount(&$arr, $order)
    {
        if ($order->getDiscountAmount() < 0) {
            $arr[] = [
                "title"       => "Store discount coupon",
                "description" => "Store discount coupon",
                "category_id" => $this->_scopeConfig->getValue('payment/mercadopago/category_id', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                "quantity"    => 1,
                "unit_price"  => (float)$order->getDiscountAmount()
            ];
        }
    }

    /**
     * @param $arr
     * @param $order
     */
    protected function _calculateBaseTaxAmount(&$arr, $order)
    {
        if ($order->getBaseTaxAmount() > 0) {
            $arr[] = [
                "title"       => "Store taxes",
                "description" => "Store taxes",
                "category_id" => $this->_scopeConfig->getValue('payment/mercadopago/category_id', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                "quantity"    => 1,
                "unit_price"  => (float)$order->getBaseTaxAmount()
            ];
        }
    }

    /**
     * Return total price of all items
     *
     * @param $items
     *
     * @return int
     */
    protected function getTotalItems($items)
    {
        $total = 0;
        foreach ($items as $item) {
            $total += $item['unit_price'] * $item['quantity'];
        }

        return $total;
    }

    /**
     * @return array
     */
    protected function getExcludedPaymentsMethods()
    {
        $excludedMethods = [];
        $excluded_payment_methods = $this->getConfigData('excluded_payment_methods');
        $arr_epm = explode(",", $excluded_payment_methods);
        if (count($arr_epm) > 0) {
            foreach ($arr_epm as $m) {
                $excludedMethods[] = ["id" => $m];
            }
        }

        return $excludedMethods;
    }

    /**
     * Return info of shipping address
     *
     * @param $shippingAddress
     *
     * @return array
     */
    protected function getReceiverAddress($shippingAddress)
    {
        return [
            "floor"         => "-",
            "zip_code"      => $shippingAddress->getPostcode(),
            "street_name"   => $shippingAddress->getStreet()[0] . " - " . $shippingAddress->getCity() . " - " . $shippingAddress->getCountryId(),
            "apartment"     => "-",
            "street_number" => ""
        ];
    }

    /**
     * @return mixed
     */
    public function getBannerCheckoutUrl()
    {
        return $this->getConfigData('banner_checkout');
    }

    /**
     * @return string
     */
    public function getActionUrl()
    {
        return $this->_urlBuilder->getUrl(self::ACTION_URL);
    }

    /**
     * Check whether payment method can be used
     *
     * @param \Magento\Quote\Api\Data\CartInterface|null $quote
     *
     * @return bool
     */
    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        $parent = parent::isAvailable($quote);
        $clientId = $this->_scopeConfig->getValue(\MercadoPago\Core\Helper\Data::XML_PATH_CLIENT_ID, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $clientSecret = $this->_scopeConfig->getValue(\MercadoPago\Core\Helper\Data::XML_PATH_CLIENT_SECRET, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $standard = (!empty($clientId) && !empty($clientSecret));

        if (!$parent || !$standard) {
            return false;
        }

        return $this->_helperData->isValidClientCredentials($clientId, $clientSecret);

    }

    /**
     * Return success page url
     *
     * @return string
     */
    public function getOrderPlaceRedirectUrl()
    {
        $url = $this->_helperData->getSuccessUrl();

        return $this->_urlBuilder->getUrl($url, ['_secure' => true]);
    }

}