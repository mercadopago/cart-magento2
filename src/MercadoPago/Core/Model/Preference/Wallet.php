<?php

namespace MercadoPago\Core\Model\Preference;

use Exception;
use Magento\Catalog\Helper\Image;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\Data\CustomerInterface as DataCustomerInterface;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Api\ExtensibleDataInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Payment\Model\Method\Logger;
use Magento\Quote\Api\CartManagementInterface as CartManagement;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item;
use Magento\Quote\Model\QuoteManagement;
use Magento\Quote\Model\QuoteRepository;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Store\Model\ScopeInterface;
use MercadoPago\Core\Block\Adminhtml\System\Config\Version;
use MercadoPago\Core\Helper\ConfigData;
use MercadoPago\Core\Helper\Data;
use MercadoPago\Core\Helper\Round;
use MercadoPago\Core\Helper\SponsorId;
use MercadoPago\Core\Lib\Api;
use MercadoPago\Core\Model\Notifications\Topics\Payment;

/**
 * Class Wallet
 */
class Wallet
{
    const PURPOSE_WALLET_PURCHASE = 'wallet_purchase';

    const NOTIFICATION_PATH = 'mercadopago/wallet/notification';

    const SUCCESS_PATH = 'mercadopago/wallet/success';

    const FAILURE_PATH = 'mercadopago/wallet/failure';

    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    /**
     * @var CustomerSession
     */
    protected $customerSession;

    /**
     * @var Version
     */
    protected $version;

    /**
     * @var ProductMetadataInterface
     */
    protected $productMetadata;

    /**
     * @var Image
     */
    protected $helperImage;

    /**
     * @var Data
     */
    protected $helperData;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var QuoteRepository
     */
    protected $quoteRepository;

    /**
     * @var CartManagement
     */
    protected $quoteManagement;

    /**
     * @var Basic
     */
    protected $preferenceBasic;

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var Payment
     */
    protected $paymentNotification;

    /**
     * @var OrderInterface
     */
    protected $order;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * Wallet constructor.
     *
     * @param CheckoutSession          $checkoutSession
     * @param CustomerSession          $customerSession
     * @param Version                  $version
     * @param ProductMetadataInterface $productMetadata
     * @param Image                    $helperImage
     * @param Data                     $helperData
     * @param ScopeConfigInterface     $scopeConfig
     * @param QuoteRepository          $quoteRepository
     * @param QuoteManagement          $quoteManagement
     * @param Basic                    $preferenceBasic
     * @param UrlInterface             $urlBuilder
     * @param Payment                  $paymentNotification
     * @param OrderInterface           $order
     * @param Logger                   $logger
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        CustomerSession $customerSession,
        Version $version,
        ProductMetadataInterface $productMetadata,
        Image $helperImage,
        Data $helperData,
        ScopeConfigInterface $scopeConfig,
        QuoteRepository $quoteRepository,
        QuoteManagement $quoteManagement,
        Basic $preferenceBasic,
        UrlInterface $urlBuilder,
        Payment $paymentNotification,
        OrderInterface $order,
        Logger $logger
    ) {
        $this->checkoutSession     = $checkoutSession;
        $this->customerSession     = $customerSession;
        $this->version             = $version;
        $this->productMetadata     = $productMetadata;
        $this->helperImage         = $helperImage;
        $this->helperData          = $helperData;
        $this->scopeConfig         = $scopeConfig;
        $this->quoteRepository     = $quoteRepository;
        $this->quoteManagement     = $quoteManagement;
        $this->preferenceBasic     = $preferenceBasic;
        $this->urlBuilder          = $urlBuilder;
        $this->paymentNotification = $paymentNotification;
        $this->order               = $order;
        $this->logger              = $logger;
    }//end __construct()

    /**
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function makePreference()
    {
        $preference = $this->buildPreferenceArray();
        return $this->getMercadoPagoInstance()->create_preference($preference);
    }//end makePreference()

    /**
     * @param  $merchantOrder
     * @return OrderInterface
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws CouldNotSaveException
     * @throws Exception
     */
    public function processNotification($merchantOrder)
    {
        $preference = $this->loadPreference($merchantOrder['preference_id']);

        if (!$preference) {
            throw new Exception(__('Preference #%1 not found!', $merchantOrder['preference_id']), 404);
        }

        $totalQuantity = count($merchantOrder['payments']);
        $hasOrder      = $this->loadOrderByIncrementalId($merchantOrder['external_reference']);

        if ($hasOrder->getIncrementId()) {
            return $hasOrder;
        }

        if (!in_array($merchantOrder['order_status'], ['paid', 'partially_paid']) || !$totalQuantity) {
            throw new Exception(__('Payment #%1 not found!'), 400);
        }

        $lastPayment = $merchantOrder['payments'][($totalQuantity - 1)];
        $payment     = $this->loadPayment($lastPayment['id']);

        $order = $this->createOrderByPaymentWithQuote($payment);

        if (!$order->getIncrementId()) {
            throw new Exception(__('Error to create. Order #%1 not available yet.', $merchantOrder['external_reference']), 500);
        }

        return $order;
    }//end processNotification()

    /**
     * @param  $paymentId
     * @param CheckoutSession $session
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws CouldNotSaveException
     * @throws Exception
     */
    public function processSuccessRequest($paymentId, CheckoutSession $session)
    {
        $payment            = $this->loadPayment($paymentId);
        $orderIncrementalId = $payment['external_reference'];

        $order = $this->loadOrderByIncrementalId($orderIncrementalId);

        if (!$order->getIncrementId()) {
            $quote = $session->getQuote();
            $quote->getPayment()->setMethod('mercadopago_custom');
            $order = $this->createOrderByPaymentWithQuote($payment);
        }

        if (!$order->getIncrementId()) {
            throw new Exception(__("Sorry, we can't create a order with external reference #%1", $orderIncrementalId));
        }

        $this->paymentNotification->updateStatusOrderByPayment($payment);

        $session->setLastSuccessQuoteId($payment['metadata']['quote_id']);
        $session->setLastQuoteId($payment['metadata']['quote_id']);
        $session->setLastOrderId($payment['external_reference']);
        $session->setLastRealOrderId($payment['external_reference']);
    }//end processSuccessRequest()

    /**
     * @param $payment
     * @return OrderInterface
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws CouldNotSaveException
     */
    protected function createOrderByPaymentWithQuote($payment)
    {
        $quoteId = $payment['metadata']['quote_id'];

        $quote = $this->quoteRepository->get($quoteId);
        $quote->getPayment()->setAdditionalInformation('purpose', 'wallet_purchase');
        $quote->getPayment()->importData(['method' => 'mercadopago_basic']);

        $orderId = $this->quoteManagement->placeOrder($quote->getId());

        return $this->loadOrderById($orderId);
    }//end createOrderByPaymentWithQuote()

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
     * @param  $merchantOrderId
     * @return mixed
     * @throws LocalizedException
     * @throws Exception
     */
    protected function loadMerchantOrder($merchantOrderId)
    {
        $response = $this->getMercadoPagoInstance()->get_merchant_order($merchantOrderId);
        if (!empty($response['response']) && $response['status'] < 300) {
            return $response['response'];
        }

        throw new Exception(__('Merchant Order not found or is an notification invalid type.'));
    }//end loadMerchantOrder()

    /**
     * @param  $preferenceId
     * @return mixed
     * @throws LocalizedException
     * @throws Exception
     */
    protected function loadPreference($preferenceId)
    {
        $response = $this->getMercadoPagoInstance()->get_preference($preferenceId);
        if (!empty($response['response']) && $response['status'] < 300) {
            return $response['response'];
        }

        throw new Exception(__('Preference #%1 not found!', $preferenceId));
    }//end loadPreference()

    /**
     * @param  $paymentId
     * @return mixed
     * @throws LocalizedException
     * @throws Exception
     */
    protected function loadPayment($paymentId)
    {
        $response = $this->getMercadoPagoInstance()->get("/v1/payments/{$paymentId}");
        if (!empty($response['response']) && $response['status'] < 300) {
            return $response['response'];
        }

        throw new Exception(__('Payment #%1 not found!', $paymentId));
    }//end loadPayment()

    /**
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    protected function buildPreferenceArray()
    {
        $quote      = $this->getQuote();
        $preference = $this->getPreference();
        $siteId     = $preference['metadata']['site'];
        $customer   = $this->getCustomer();
        $quote->reserveOrderId();

        $preference['external_reference'] = $quote->getReservedOrderId();
        $preference['items']              = $this->getItems($quote, $siteId);
        $preference['payer']              = $this->getPayer($quote, $customer);

        if (!$customer->getId()) {
            $quote->setCustomerIsGuest(true);
            $quote->setCustomerEmail($preference['payer']['email']);
            $quote->setCustomerFirstname($preference['payer']['name']);
            $quote->setCustomerLastname($preference['payer']['surname']);
        }

        $this->quoteRepository->save($quote);

        if (!$quote->getShippingAddress()) {
            unset($preference['shipments']);
        }

        $preference['shipments']['cost'] = Round::roundWithSiteId(
            $quote->getShippingAddress()->getShippingAmount(),
            $siteId
        );

        $preference['metadata']['test_mode'] = $this->isTestMode($preference['payer']);
        $preference['metadata']['quote_id']  = $quote->getId();

        if ($this->getGatewayMode()) {
            $preference['gateway_mode'] = ['gateway'];
        }

        return $preference;
    }//end buildPreferenceArray()

    /**
     * @return array
     */
    protected function getPreference()
    {
        $this->version->afterLoad();

        return [
            'items'                => [],
            'payer'                => [],
            'back_urls'            => [
                'success' => $this->urlBuilder->getUrl(self::SUCCESS_PATH),
                'pending' => $this->urlBuilder->getUrl(self::SUCCESS_PATH),
                'failure' => $this->urlBuilder->getUrl(self::FAILURE_PATH),
            ],
            'payment_methods'      => [
                'excluded_payment_methods' => $this->getExcludedPaymentMethods(),
                'excluded_payment_types'   => [],
                'installments'             => $this->getMaxInstallments(),
            ],
            'shipments'            => [
                'mode' => 'not_specified',
                'cost' => 0.00,
            ],
            'notification_url'     => $this->getNotificationUrl(),
            'statement_descriptor' => $this->getStateDescriptor(),
            'external_reference'   => '',
            'binary_mode'          => $this->getBinaryMode(),
            'purpose'              => self::PURPOSE_WALLET_PURCHASE,
            'metadata'             => [
                'site'             => $this->getSiteId(),
                'platform'         => 'BP1EF6QIC4P001KBGQ10',
                'platform_version' => $this->productMetadata->getVersion(),
                'module_version'   => $this->version->getValue(),
                'sponsor_id'       => $this->getSponsorId(),
                'test_mode'        => '',
                'quote_id'         => '',
                'checkout'         => 'pro',
                'checkout_type'    => 'wallet_button',
            ],
        ];
    }//end getPreference()

    /**
     * @return array
     */
    protected function getExcludedPaymentMethods()
    {
        $excluded = [];
        $configExcludedPaymentMethods = $this->getConfig(ConfigData::PATH_CUSTOM_EXCLUDE_PAYMENT_METHODS);
        $excludedPaymentMethods = is_string($configExcludedPaymentMethods) ?
             explode(',', $configExcludedPaymentMethods) : [];

        foreach ($excludedPaymentMethods as $paymentMethod) {
            $excluded[] = ['id' => $paymentMethod];
        }

        return $excluded;
    }//end getExcludedPaymentMethods()

    /**
     * @param  $path
     * @param  string $scopeType
     * @return mixed
     */
    protected function getConfig($path, $scopeType = ScopeInterface::SCOPE_STORE)
    {
        return $this->scopeConfig->getValue($path, $scopeType);
    }//end getConfig()

    /**
     * @return integer
     */
    protected function getMaxInstallments()
    {
        return (int) $this->getConfig(ConfigData::PATH_BASIC_MAX_INSTALLMENTS);
    }//end getMaxInstallments()

    /**
     * @return mixed
     */
    protected function getStateDescriptor()
    {
        return $this->getConfig(ConfigData::PATH_CUSTOM_STATEMENT_DESCRIPTOR);
    }//end getStateDescriptor()

    /**
     * @return boolean
     */
    protected function getBinaryMode()
    {
        $value = $this->getConfig(ConfigData::PATH_CUSTOM_BINARY_MODE);
        return (bool) $value;
    }//end getBinaryMode()

    /**
     * @return false|string|string[]
     */
    protected function getSiteId()
    {
        return mb_strtoupper($this->getConfig(ConfigData::PATH_SITE_ID));
    }//end getSiteId()

    /**
     * @return CartInterface|Quote
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    protected function getQuote()
    {
        return $this->checkoutSession->getQuote();
    }//end getQuote()

    /**
     * @return DataCustomerInterface|Customer|ExtensibleDataInterface
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    protected function getCustomer()
    {
        return $this->getQuote()->getCustomer();
    }//end getCustomer()

    /**
     * @param  Quote $quote
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

        $tax = $this->getTaxAmount($quote);
        if ($tax > 0) {
            $items[] = $this->getItemDiscountTax(__('Tax'), $tax, $siteId);
        }

        return $items;
    }//end getItems()

    /**
     * @param Quote $quote
     * @return float
     */
    protected function getDiscountAmount(Quote $quote)
    {
        return ($quote->getSubtotalWithDiscount() - $quote->getBaseSubtotal());
    }//end processDiscount()

    /**
     * @param Quote $quote
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
     * @param  Item $item
     * @param  $categoryId
     * @param  $siteId
     * @return array
     */
    protected function getItem(Item $item, $categoryId, $siteId)
    {
        $product = $item->getProduct();
        $image   = $this->helperImage->init($product, 'product_thumbnail_image');

        return [
            'id'          => $item->getSku(),
            'title'       => $product->getName(),
            'description' => $product->getName(),
            'picture_url' => $image->getUrl(),
            'category_id' => $categoryId,
            'quantity'    => Round::roundInteger($item->getQty()),
            'unit_price'  => Round::roundWithSiteId($item->getPrice(), $siteId),
        ];
    }//end getItem()

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
        $createdAt = $customer->getId() ? strtotime($customer->getCreatedAt()) : time();
        $email     = $data->getEmail() ? $data->getEmail() : $shipping->getEmail();

        return [
            'email'        => htmlentities($email),
            'name'         => htmlentities($data->getFirstname()),
            'surname'      => htmlentities($data->getLastname()),
            'date_created' => date('c', $createdAt),
            'address'      => [
                'zip_code'      => $billing->getPostcode(),
                'street_name'   => sprintf(
                    '%s - %s - %s - %s',
                    implode(', ', $billing->getStreet()),
                    $billing->getCity(),
                    $billing->getRegion(),
                    $billing->getCountry()
                ),
                'street_number' => '',
            ],
        ];
    }//end getPayer()

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
     * @return int|null
     */
    protected function getSponsorId()
    {
        return SponsorId::getSponsorId($this->getSiteId());
    }//end getSponsorId()

    /**
     * @return boolean
     */
    protected function getGatewayMode()
    {
        $value = $this->getConfig(ConfigData::PATH_CUSTOM_GATEWAY_MODE);
        return (bool) $value;
    }//end getGatewayMode()

    /**
     * @return Api
     * @throws LocalizedException
     */
    public function getMercadoPagoInstance()
    {
        return $this->helperData->getApiInstance($this->getAccessToken());
    }//end getMercadoPagoInstance()

    /**
     * @return mixed
     */
    protected function getAccessToken()
    {
        return $this->getConfig(ConfigData::PATH_ACCESS_TOKEN);
    }//end getAccessToken()

    /**
     * @return string|void
     */
    protected function getNotificationUrl()
    {
        $params = [
            '_query' => [
                'source_news' => 'ipn'
            ]
        ];

        $notification_url = $this->urlBuilder->getUrl(self::NOTIFICATION_PATH, $params);

        if (strrpos($notification_url, 'localhost')) {
            return;
        }

        return $notification_url;
    }
}//end class
