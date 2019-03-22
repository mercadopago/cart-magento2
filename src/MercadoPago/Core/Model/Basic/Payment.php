<?php

namespace MercadoPago\Core\Model\Basic;

use Magento\Catalog\Helper\Image;
use Magento\Checkout\Model\Session;
use Magento\Customer\Model\Session as customerSession;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\DataObject;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Magento\Payment\Helper\Data;
use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Payment\Model\Method\Logger;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Model\OrderFactory;
use Magento\Store\Model\ScopeInterface;
use MercadoPago\Core\Helper\ConfigData;
use MercadoPago\Core\Helper\Data as dataHelper;

/**
 * Class Payment
 * @package MercadoPago\Core\Model\Basic
 */
class Payment extends AbstractMethod
{
    const CODE = 'mercadopago_basic';
    const ACTION_URL = 'mercadopago/basic/pay';
    const FAILURE_URL = 'mercadopago/basic/failure';


    /**
     *  Self fields
     */
    protected $_scopeConfig;
    protected $_helperData;
    protected $_helperImage;
    protected $_checkoutSession;
    protected $_customerSession;
    protected $_orderFactory;
    protected $_urlBuilder;

    /**
     *  Overrides fields
     */
    protected $_code = self::CODE;
    protected $_isGateway = true;
    protected $_canOrder = true;
    protected $_canAuthorize = true;
    protected $_canCapture = true;
    protected $_canCapturePartial = true;
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = true;
    protected $_canVoid = true;
    protected $_canUseInternal = false;
    protected $_canFetchTransactionInfo = true;
    protected $_canReviewPayment = true;
    protected $_infoBlockType = 'MercadoPago\Core\Block\Info';

    /**
     * Payment constructor.
     * @param dataHelper $helperData
     * @param Image $helperImage
     * @param Session $checkoutSession
     * @param customerSession $customerSession
     * @param OrderFactory $orderFactory
     * @param UrlInterface $urlBuilder
     * @param Context $context
     * @param Registry $registry
     * @param ExtensionAttributesFactory $extensionFactory
     * @param AttributeValueFactory $customAttributeFactory
     * @param Data $paymentData
     * @param ScopeConfigInterface $scopeConfig
     * @param Logger $logger
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        dataHelper $helperData,
        Image $helperImage,
        Session $checkoutSession,
        customerSession $customerSession,
        OrderFactory $orderFactory,
        UrlInterface $urlBuilder,
        Context $context,
        Registry $registry,
        ExtensionAttributesFactory $extensionFactory,
        AttributeValueFactory $customAttributeFactory,
        Data $paymentData,
        ScopeConfigInterface $scopeConfig,
        Logger $logger,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
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
        $this->_scopeConfig = $scopeConfig;
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function postPago()
    {
        $response = $this->makePreference();
        if ($response['status'] == 200 || $response['status'] == 201) {
            $payment = $response['response'];
            if ($this->_scopeConfig->getValue(ConfigData::PATH_BASIC_SANDBOX_MODE, ScopeInterface::SCOPE_STORE)) {
                $init_point = $payment['sandbox_init_point'];
            } else {
                $init_point = $payment['init_point'];
            }
            $array_assign = [
                "init_point" => $init_point,
                "type_checkout" => $this->getConfigData('type_checkout'),
                "iframe_width" => $this->getConfigData('iframe_width'),
                "iframe_height" => $this->getConfigData('iframe_height'),
                "banner_checkout" => $this->getConfigData('banner_checkout'),
                "status" => 201
            ];
            $this->_helperData->log("Array preference ok", 'mercadopago-basic.log');
        } else {
            $array_assign = [
                "message" => __('An error has occurred. Please refresh the page.'),
                "json" => json_encode($response),
                "status" => 400
            ];
            $this->_helperData->log("Array preference error", 'mercadopago-basic.log');
        }
        return $array_assign;
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function makePreference()
    {
        $orderIncrementId = $this->_checkoutSession->getLastRealOrderId();
        $orderIncrementId = '000000009';
        $order = $this->_orderFactory->create()->loadByIncrementId($orderIncrementId);
        $payment = $order->getPayment();
        $customer = $this->_customerSession->getCustomer();

        $installments = $this->_scopeConfig->getValue(ConfigData::PATH_BASIC_MAX_INSTALLMENTS, ScopeInterface::SCOPE_STORE);
        $auto_return = $this->_scopeConfig->getValue(ConfigData::PATH_BASIC_AUTO_RETURN, ScopeInterface::SCOPE_STORE);
        $successPage = $this->_scopeConfig->getValue(ConfigData::PATH_ADVANCED_SUCCESS_PAGE, ScopeInterface::SCOPE_STORE);
        $sponsor_id = $this->_scopeConfig->getValue(ConfigData::PATH_SPONSOR_ID, ScopeInterface::SCOPE_STORE);
        $category_id = $this->_scopeConfig->getValue(ConfigData::PATH_ADVANCED_CATEGORY, ScopeInterface::SCOPE_STORE);
        $country = $this->_scopeConfig->getValue(ConfigData::PATH_ADVANCED_COUNTRY, ScopeInterface::SCOPE_STORE);
        $accessToken = $this->_scopeConfig->getValue(ConfigData::PATH_ACCESS_TOKEN, ScopeInterface::SCOPE_STORE);

        $paramsShipment = new DataObject();
        $paramsShipment->setParams([]);

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
                "title" => "Difference amount of the items with a total",
                "description" => "Difference amount of the items with a total",
                "category_id" => $category_id,
                "quantity" => 1,
                "unit_price" => (float)$diff_price
            ];
            $this->_helperData->log("Total itens: " . $total_item, 'mercadopago-basic.log');
            $this->_helperData->log("Total order: " . $order_amount, 'mercadopago-basic.log');
            $this->_helperData->log("Difference add itens: " . $diff_price, 'mercadopago-basic.log');
        }

        if ($order->canShip()) {
            $shippingAddress = $order->getShippingAddress();
            $shipping = $shippingAddress->getData();
            $arr['payer']['phone'] = [
                "area_code" => "-",
                "number" => $shipping['telephone']
            ];
            $arr['shipments'] = [];
            $arr['shipments']['receiver_address'] = $this->getReceiverAddress($shippingAddress);
            $arr['items'][] = [
                "title" => "Shipment cost",
                "description" => "Shipment cost",
                "category_id" => $category_id,
                "quantity" => 1,
                "unit_price" => (float)$order->getBaseShippingAmount()
            ];
        }

        $billingAddress = $order->getBillingAddress()->getData();
        $arr['payer']['date_created'] = date('Y-m-d', $customer->getCreatedAtTimestamp()) . "T" . date('H:i:s', $customer->getCreatedAtTimestamp());
        $arr['payer']['email'] = $customer->getId() ? htmlentities($customer->getEmail()) : htmlentities($billingAddress['email']);
        $arr['payer']['first_name'] = $customer->getId() ? htmlentities($customer->getFirstname()) : htmlentities($billingAddress['firstname']);
        $arr['payer']['last_name'] = $customer->getId() ? htmlentities($customer->getLastname()) : htmlentities($billingAddress['lastname']);

        if (isset($payment['additional_information']['doc_number']) && $payment['additional_information']['doc_number'] != "") {
            $arr['payer']['identification'] = [
                "type" => "CPF",
                "number" => $payment['additional_information']['doc_number']
            ];
        }

        $arr['payer']['address'] = [
            "zip_code" => $billingAddress['postcode'],
            "street_name" => $billingAddress['street'] . " - " . $billingAddress['city'] . " - " . $billingAddress['country_id'],
            "street_number" => ""
        ];

        $successUrl = $successPage ? 'mercadopago/checkout/page' : 'checkout/onepage/success';
        $arr['back_urls']['success'] = $this->_urlBuilder->getUrl($successUrl);
        $arr['back_urls']['pending'] = $this->_urlBuilder->getUrl($successUrl);
        $arr['back_urls']['failure'] = $successPage ? $this->_urlBuilder->getUrl('mercadopago/standard/failure') : $this->_urlBuilder->getUrl('checkout/onepage/failure');
        $arr['notification_url'] = $this->_urlBuilder->getUrl("mercadopago/notifications/standard");
        $arr['payment_methods']['excluded_payment_methods'] = $this->getExcludedPaymentsMethods();
        $arr['payment_methods']['installments'] = (int)$installments;

        if ($auto_return == 1) {
            $arr['auto_return'] = "approved";
        }

        $this->_helperData->log("Sponsor_id", 'mercadopago-basic.log', $sponsor_id);
        if (!empty($sponsor_id)) {
            $this->_helperData->log("Sponsor_id identificado", 'mercadopago-standard.log', $sponsor_id);
            $arr['sponsor_id'] = (int)$sponsor_id;
        }

        $siteId = strtoupper($country);
        if ($siteId == 'MLC' || $siteId == 'MCO') {
            foreach ($arr['items'] as $key => $item) {
                $arr['items'][$key]['unit_price'] = (int)$item['unit_price'];
            }
        }

        $mpApiInstance = $this->_helperData->getApiInstance($accessToken);
        $this->_helperData->log("make array", 'mercadopago-basic.log', $arr);
        $response = $mpApiInstance->create_preference($arr);
        $this->_helperData->log("create preference result", 'mercadopago-basic.log', $response);

        return $response;
    }

    /**
     * @param $params
     * @param $order
     * @param $shippingAddress
     * @return mixed
     */
    protected function _getParamShipment($params, $order, $shippingAddress)
    {
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
     * @param $order
     * @return array
     */
    protected function getItems($order)
    {
        $items = [];
        foreach ($order->getAllVisibleItems() as $item) {
            $product = $item->getProduct();
            $image = $this->_helperImage->init($product, 'image');
            $items[] = [
                "id" => $item->getSku(),
                "title" => $product->getName(),
                "description" => $product->getName(),
                "picture_url" => $image->getUrl(),
                "category_id" => $this->_scopeConfig->getValue(ConfigData::PATH_ADVANCED_CATEGORY, ScopeInterface::SCOPE_STORE),
                "quantity" => (int)number_format($item->getQtyOrdered(), 0, '.', ''),
                "unit_price" => (float)number_format($item->getPrice(), 2, '.', '')
            ];
        }
        return $items;
    }

    /**
     * @param $arr
     * @param $order
     */
    protected function _calculateDiscountAmount(&$arr, $order)
    {
        if ($order->getDiscountAmount() < 0) {
            $arr[] = [
                "title" => "Store discount coupon",
                "description" => "Store discount coupon",
                "category_id" => $this->_scopeConfig->getValue(ConfigData::PATH_ADVANCED_CATEGORY, ScopeInterface::SCOPE_STORE),
                "quantity" => 1,
                "unit_price" => (float)$order->getDiscountAmount()
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
                "title" => "Store taxes",
                "description" => "Store taxes",
                "category_id" => $this->_scopeConfig->getValue(ConfigData::PATH_ADVANCED_CATEGORY, ScopeInterface::SCOPE_STORE),
                "quantity" => 1,
                "unit_price" => (float)$order->getBaseTaxAmount()
            ];
        }
    }

    /**
     * @param $items
     * @return float|int
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
        $excluded_payment_methods = $this->_scopeConfig->getValue(ConfigData::PATH_BASIC_EXCLUDE_PAYMENT_METHODS, ScopeInterface::SCOPE_STORE);
        $arr_epm = explode(",", $excluded_payment_methods);
        if (count($arr_epm) > 0) {
            foreach ($arr_epm as $m) {
                $excludedMethods[] = ["id" => $m];
            }
        }
        return $excludedMethods;
    }

    /**
     * @param $shippingAddress
     * @return array
     */
    protected function getReceiverAddress($shippingAddress)
    {
        return [
            "floor" => "-",
            "zip_code" => $shippingAddress->getPostcode(),
            "street_name" => $shippingAddress->getStreet()[0] . " - " . $shippingAddress->getCity() . " - " . $shippingAddress->getCountryId(),
            "apartment" => "-",
            "street_number" => ""
        ];
    }

    /**
     * @return mixed
     */
    public function getBannerCheckoutUrl()
    {
        return $this->_scopeConfig->getValue(ConfigData::PATH_BASIC_BANNER, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return string
     */
    public function getActionUrl()
    {
        return $this->_urlBuilder->getUrl(self::ACTION_URL);
    }

    /**
     * @param CartInterface|null $quote
     * @return bool
     */
    public function isAvailable(CartInterface $quote = null)
    {
        return parent::isAvailable($quote);
    }

    /**
     * @return string
     */
    public function getOrderPlaceRedirectUrl()
    {
        $successPage = $this->_scopeConfig->getValue(ConfigData::PATH_ADVANCED_SUCCESS_PAGE, ScopeInterface::SCOPE_STORE);
        $successUrl = $successPage ? 'mercadopago/checkout/page' : 'checkout/onepage/success';
        return $this->_urlBuilder->getUrl($successUrl, ['_secure' => true]);
    }
}
