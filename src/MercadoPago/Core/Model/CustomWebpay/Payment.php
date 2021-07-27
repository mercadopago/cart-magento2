<?php

namespace MercadoPago\Core\Model\CustomWebpay;

use Exception;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Checkout\Model\Cart;
use Magento\Customer\Api\Data\CustomerInterface;
use MercadoPago\Core\Helper\ConfigData;
use MercadoPago\Core\Helper\Round;
use MercadoPago\Core\Helper\Response;

/**
 * Class Payment
 */
class Payment extends \MercadoPago\Core\Model\Custom\Payment
{
    /**
     * Define callback endpoints
     */
    const SUCCESS_PATH = 'mercadopago/customwebpay/success';
    const FAILURE_PATH = 'mercadopago/customwebpay/failure';
    const NOTIFICATION_PATH = 'mercadopago/customwebpay/notification';

    /**
     * Define payment method code
     */
    const CODE = 'mercadopago_custom_webpay';

    /**
     * log filename
     */
    const LOG_NAME = 'custom_webpay';

    /**
     * @var string
     */
    protected $_code = self::CODE;

    /**
     * @var string
     */
    protected $_infoBlockType = 'MercadoPago\Core\Block\CustomWebpay\Info';

    /**
     * @param string $paymentAction
     * @param object $stateObject
     *
     * @return boolean
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @throws LocalizedException
     */
    public function initialize($paymentAction, $stateObject) {}

    /**
     * is payment method available?
     *
     * @param CartInterface|null $quote
     *
     * @return boolean
     */
    public function isAvailable(CartInterface $quote = null) {
        $isActive = $this->_scopeConfig->getValue(ConfigData::PATH_CUSTOM_WEBPAY_ACTIVE, ScopeInterface::SCOPE_STORE);

        if (empty($isActive)) {
            return false;
        }

        return parent::isAvailableMethod($quote);
    }//end isAvailable()

    /**
     * @param  DataObject $data
     * @return $this|\MercadoPago\Core\Model\Custom\Payment
     * @throws LocalizedException
     */
    public function assignData(DataObject $data) {
        if (!($data instanceof DataObject)) {
            $data = new DataObject($data);
        }

        $infoForm = $data->getData();

        if (isset($infoForm['additional_data'])) {
            if (empty($infoForm['additional_data'])) {
                return $this;
            }

            $info = $this->getInfoInstance();
            $info->setAdditionalInformation('method', $infoForm['method']);
        }

        return $this;
    }//end assignData

    /**
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function createPayment($quoteId, $token, $paymentMethodId, $issuerId, $installments)
    {
        $preference = $this->makePreference($quoteId, $token, $paymentMethodId, $issuerId, $installments);
        return $this->_coreModel->postPaymentV1($preference);
    }//end createPayment()

    /**
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function makePreference($quoteId, $token, $paymentMethodId, $issuerId, $installments)
    {
        $quote      = $this->getReservedQuote($quoteId);
        $preference = $this->getPreference();
        $customer   = $this->getCustomer($quoteId);
        $siteId     = $preference['metadata']['site'];

        $preference['additional_info']['items'] = $this->getItems($quote, $siteId);
        $preference['additional_info']['payer'] = $this->getPayer($quote, $customer);
        $preference['token']                    = $token;
        $preference['issuer_id']                = $issuerId;
        $preference['installments']             = (int) $installments;
        $preference['payment_method_id']        = $paymentMethodId;
        $preference['external_reference']       = $quoteId;
        $preference['payer']['email']           = $preference['additional_info']['payer']['email'];
        $preference['transaction_amount']       = Round::roundWithSiteId($quote->getBaseGrandTotal(), $siteId);

        if (!$customer->getId()) {
            $quote->setCustomerIsGuest(true);
            $quote->setCustomerEmail($preference['payer']['email']);
            $quote->setCustomerFirstname($preference['additional_info']['payer']['first_name']);
            $quote->setCustomerLastname($preference['additional_info']['payer']['last_name']);
        }

        if ($quote->getShippingAddress()) {
            $preference['additional_info']['shipments'] = $this->getShipments($quote);
        }

        $preference['metadata']['test_mode'] = $this->isTestMode($preference['payer']);
        $preference['metadata']['quote_id']  = $quote->getId();

        unset($preference['additional_info']['payer']['email']);

        return $preference;
    }//end makePreference()

    /**
     * @return Cart
     */
    public function getCartObject()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        return $objectManager->get('\Magento\Checkout\Model\Cart');
    }//end getCartObject()

    /**
     * @return void
     */
    public function reserveQuote()
    {
        return $this->getCartObject()->getQuote()->reserveOrderId();
    }//end reserveQuote()

    /**
     * @return string
     */
    public function getReservedQuoteId()
    {
        return $this->getCartObject()->getQuote()->getId();
    }//end getReservedQuoteId()

    /**
     * @return Quote
     */
    public function getReservedQuote($quoteId)
    {
        return $this->_quoteRepository->get($quoteId);
    }//end getReservedQuote()

    /**
     * @return array
     */
    protected function getPreference()
    {
        $this->_version->afterLoad();

        return [
            'additional_info' => [
                'items'     => [],
                'payer'     => [],
                'shipments' => [],
            ],
            'notification_url'     => $this->_urlBuilder->getUrl(self::NOTIFICATION_PATH),
            'statement_descriptor' => $this->getStateDescriptor(),
            'external_reference'   => '',
            'metadata'             => [
                'site'             => $this->getSiteId(),
                'platform'         => 'BP1EF6QIC4P001KBGQ10',
                'platform_version' => $this->_productMetadata->getVersion(),
                'module_version'   => $this->_version->getValue(),
                'sponsor_id'       => $this->getSponsorId(),
                'test_mode'        => '',
                'quote_id'         => '',
                'checkout'         => 'custom',
                'checkout_type'    => 'webpay',
            ],
        ];
    }//end getPreference()

    /**
     * @param  $path
     * @param  string $scopeType
     * @return mixed
     */
    protected function getConfig($path, $scopeType=ScopeInterface::SCOPE_STORE)
    {
        return $this->_scopeConfig->getValue($path, $scopeType);
    }//end getConfig()

    /**
     * @return mixed
     */
    protected function getStateDescriptor()
    {
        return $this->getConfig(ConfigData::PATH_CUSTOM_STATEMENT_DESCRIPTOR);
    }//end getStateDescriptor()

    /**
     * @return false|string|string[]
     */
    protected function getSiteId()
    {
        return mb_strtoupper($this->getConfig(ConfigData::PATH_SITE_ID));
    }//end getSiteId()

    /**
     * @return integer|null
     */
    protected function getSponsorId()
    {
        $sponsorId = $this->getConfig(ConfigData::PATH_SPONSOR_ID);

        if (!empty($sponsorId)) {
            return (int) $sponsorId;
        }

        return null;
    }//end getSponsorId()

    /**
     * @return DataCustomerInterface|Customer|ExtensibleDataInterface|CustomerInterface
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    protected function getCustomer($quoteId)
    {
        return $this->getReservedQuote($quoteId)->getCustomer();
    }//end getCustomer()

    /**
     * @param  Quote  $quote
     * @param  $siteId
     * @return array
     */
    protected function getItems(Quote $quote, $siteId)
    {
        $items      = [];
        $categoryId = $this->getConfig(ConfigData::PATH_ADVANCED_CATEGORY);
    
        foreach ($quote->getAllVisibleItems() as $item) {
            $items[] = $this->getItem($item, $categoryId, $siteId);
        }

        $discount = $this->getDiscountAmount($quote, $siteId);
        if ($discount < 0) {
            $items[] = $this->getItemDiscountTax(__('Discount'), $discount, $siteId);
        }

        $tax = $this->getTaxAmount($quote, $siteId);
        if ($tax > 0) {
            $items[] = $this->getItemDiscountTax(__('Tax'), $tax, $siteId);
        }

        $shipping = $this->getItemShipping($quote, $siteId);
        if (!empty($shipping)) {
            $items[] = $shipping;
        }

        return $items;
    }//end getItems()

    /**
     * @param  Item   $item
     * @param  string $categoryId
     * @param  string $siteId
     * @return array
     */
    protected function getItem(Item $item, $categoryId, $siteId)
    {
        $product = $item->getProduct();
        $image   = $this->_helperImage->init($product, 'image');

        return [
            'id'          => $item->getSku(),
            'title'       => $product->getName(),
            'description' => $product->getName(),
            'picture_url' => $image->getUrl(),
            'category_id' => $categoryId,
            'quantity'    => (int) number_format($item->getQty(), 0, '.', ''),
            'unit_price'  => Round::roundWithSiteId($item->getPrice(), $siteId),
        ];
    }//end getItem()

    /**
     * @param  Quote  $quote
     * @param  $siteId
     * @return array
     */
    protected function getDiscountAmount(Quote $quote)
    {
        return ($quote->getSubtotalWithDiscount() - $quote->getBaseSubtotal());
    }//end processDiscount()

    /**
     * @param  Quote  $quote
     * @param  $siteId
     * @return float
     */
    protected function getTaxAmount(Quote $quote)
    {
        return $quote->getGrandTotal() - ($quote->getShippingAddress()->getShippingAmount() + $quote->getSubtotalWithDiscount());
    }//end processTaxes()

    /**
     * @param  $title
     * @param  $amount
     * @param  $siteId
     * @return array
     */
    protected function getItemDiscountTax($title, $amount, $siteId)
    {
        return [
            'id'          => $title,
            'title'       => $title,
            'description' => $title,
            'quantity'    => 1,
            'unit_price'  => Round::roundWithSiteId($amount, $siteId),
        ];
    }//end getItemDiscountTax()

    /**
     * @param  Quote  $quote
     * @param  string $siteId
     * @return array
     */
    protected function getItemShipping(Quote $quote, $siteId)
    {
        return [
            'id'          => __('Shipping'),
            'title'       => __('Shipping'),
            'quantity'    => 1,
            'description' => $quote->getShippingAddress()->getShippingMethod(),
            'unit_price'  => Round::roundWithSiteId($quote->getShippingAddress()->getShippingAmount(), $siteId),
        ];
    }//end getItemShipping()

    /**
     * @param  Quote             $quote
     * @param  CustomerInterface $customer
     * @return array
     */
    protected function getPayer(Quote $quote, CustomerInterface $customer)
    {
        $billing   = $quote->getBillingAddress();
        $shipping  = $quote->getShippingAddress();
        $data      = $customer->getId() ? $customer : $billing;
        $email     = $data->getEmail() ? $data->getEmail() : $shipping->getEmail();

        return [
            'email'        => htmlentities($email),
            'first_name'   => htmlentities($data->getFirstname()),
            'last_name'    => htmlentities($data->getLastname()),
            'address'      => [
                'zip_code'    => $billing->getPostcode(),
                'street_name' => sprintf(
                    '%s - %s - %s - %s',
                    implode(', ', $billing->getStreet()),
                    $billing->getCity(),
                    $billing->getRegion(),
                    $billing->getCountry()
                ),
                'street_number' => '',
            ],
            'phone' => [
                "area_code" => '00',
                "number"    => $shipping['telephone']
            ],
        ];
    }//end getPayer()

    /**
     * @param  Quote             $quote
     * @param  CustomerInterface $customer
     * @return array
     */
    protected function getShipments(Quote $quote)
    {
        $billing = $quote->getBillingAddress();

        return [
            'receiver_address' => [
                'zip_code'     => $billing->getPostcode(),
                'street_name'  => sprintf(
                    '%s - %s - %s - %s',
                    implode(', ', $billing->getStreet()),
                    $billing->getCity(),
                    $billing->getRegion(),
                    $billing->getCountry()
                ),
                'street_number' => '-',
                'apartment'     => '-',
                'floor'         => '-',
            ],
        ];
    }//end getShipments()

    /**
     * @param  $payer
     * @return boolean
     */
    protected function isTestMode($payer)
    {
        if (!empty($this->getSponsorId())) {
            return false;
        }

        if (preg_match('/@testuser\.com$/i', $payer['email'])) {
            return true;
        }

        return false;
    }//end isTestMode()

    /**
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function createOrder($payment)
    {
        echo json_encode($payment);

        $orderIncrementalId = $payment['external_reference'];
        $order = $this->loadOrderByIncrementalId($orderIncrementalId);

        if (!$order->getIncrementId()) {
            $quote = $this->_checkoutSession->getQuote();
            $quote->getPayment()->setMethod('mercado_pago_custom_webpay');
            $order = $this->createOrderByPaymentWithQuote($payment);
        }

        if (!$order->getIncrementId()) {
            throw new \Exception(__("Sorry, we can't create a order with external reference #%1", $orderIncrementalId));
        }

        $this->paymentNotification->updateStatusOrderByPayment($payment);

        $this->_checkoutSession->setLastSuccessQuoteId($payment['metadata']['quote_id']);
        $this->_checkoutSession->setLastQuoteId($payment['metadata']['quote_id']);
        $this->_checkoutSession->setLastOrderId($payment['external_reference']);
        $this->_checkoutSession->setLastRealOrderId($payment['external_reference']);
    }//end createOrder()

    /**
     * @param  $incrementalId
     * @return OrderInterface
     */
    public function loadOrderByIncrementalId($incrementalId)
    {
        return $this->order->loadByIncrementId($incrementalId);
    }//end loadOrderByIncrementalId()

    /**
     * @param  $orderId
     * @return OrderInterface
     */
    public function loadOrderById($orderId)
    {
        return $this->order->loadByAttribute('entity_id', $orderId);
    }//end loadOrderById()

    /**
     * @param  $paymentId
     * @return integer|mixed
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    protected function createOrderByPaymentWithQuote($payment)
    {
        $quoteId = $payment['metadata']['quote_id'];

        $quote = $this->_quoteRepository->get($quoteId);
        $quote->getPayment()->importData(['method' => 'mercadopago_custom_webpay']);

        $orderId = $this->quoteManagement->placeOrder($quote->getId());

        return $this->loadOrderById($orderId);
    }//end createOrderByPaymentWithQuote()
}
