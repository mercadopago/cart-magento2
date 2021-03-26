<?php

namespace MercadoPago\Core\Model\Preference;

use Magento\Catalog\Helper\Image;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\Data\CustomerInterface as DataCustomerInterface;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Api\ExtensibleDataInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Payment\Model\Method\Logger;
use Magento\Quote\Api\CartManagementInterface as CartManagement;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote as ModelQuote;
use Magento\Quote\Model\Quote\Item;
use Magento\Quote\Model\QuoteManagement;
use Magento\Quote\Model\QuoteRepository;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Store\Model\ScopeInterface;
use MercadoPago\Core\Block\Adminhtml\System\Config\Version;
use MercadoPago\Core\Helper\ConfigData;
use MercadoPago\Core\Helper\Data;
use MercadoPago\Core\Lib\Api;
use MercadoPago\Core\Model\Notifications\Topics\Payment;

/**
 * Class Wallet
 * @package MercadoPago\Core\Model\Preference
 */
class Wallet
{
    const PURPOSE_WALLET_PURCHASE = 'wallet_purchase';

    const NOTIFICATION_PATH = 'mercadopago/wallet/notification';

    const SUCCESS_PATH = 'mercadopago/wallet/success';

    const FAILURE_PATH = 'mercadopago/wallet/failure';

    const COUNTRIES_WITH_INTEGER_PRICE = [
        'MLC',
        'MLO',
    ];

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
     * @param CheckoutSession $checkoutSession
     * @param CustomerSession $customerSession
     * @param Version $version
     * @param ProductMetadataInterface $productMetadata
     * @param Image $helperImage
     * @param Data $helperData
     * @param ScopeConfigInterface $scopeConfig
     * @param QuoteRepository $quoteRepository
     * @param QuoteManagement $quoteManagement
     * @param Basic $preferenceBasic
     * @param UrlInterface $urlBuilder
     * @param Payment $paymentNotification
     * @param OrderInterface $order
     * @param Logger $logger
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
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        $this->version = $version;
        $this->productMetadata = $productMetadata;
        $this->helperImage = $helperImage;
        $this->helperData = $helperData;
        $this->scopeConfig = $scopeConfig;
        $this->quoteRepository = $quoteRepository;
        $this->quoteManagement = $quoteManagement;
        $this->preferenceBasic = $preferenceBasic;
        $this->urlBuilder = $urlBuilder;
        $this->paymentNotification = $paymentNotification;
        $this->order = $order;
        $this->logger = $logger;
    }

    /**
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function makePreference()
    {
        $preference = $this->buildPreferenceArray();
        return $this->getMercadoPagoInstance()->create_preference($preference);
    }

    /**
     * @param $merchantOrder
     * @return int|OrderInterface|mixed
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function processNotification($merchantOrder)
    {
        $preference = $this->loadPreference($merchantOrder['preference_id']);

        if (!$preference) {
            throw new \Exception(__("Preference #%1 not found!", $merchantOrder['preference_id']), 404);
        }

        $totalQuantity = count($merchantOrder['payments']);
        $hasOrder = $this->loadOrderByIncrementalId($merchantOrder['external_reference']);

        if ($hasOrder->getIncrementId()) {
            return $hasOrder;
        }

        if (!in_array($merchantOrder['order_status'], ['paid', 'partially_paid']) || !$totalQuantity) {
            throw new \Exception(__('Payment #%1 not found!'), 400);
        }

        $lastPayment = $merchantOrder['payments'][$totalQuantity - 1];
        $payment = $this->loadPayment($lastPayment['id']);

        $order = $this->createOrderByPaymentWithQuote($payment);

        if (!$order->getIncrementId()) {
            throw new \Exception(__("Error to create. Order #%1 not available yet.", $merchantOrder['external_reference']), 500);
        }

        return $order;
    }

    /**
     * @param $paymentId
     * @param CheckoutSession $session
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function processSuccessRequest($paymentId, CheckoutSession $session)
    {
        $payment = $this->loadPayment($paymentId);
        $orderIncrementalId = $payment['external_reference'];

        $order = $this->loadOrderByIncrementalId($orderIncrementalId);

        if (!$order->getIncrementId()) {
            $quote = $session->getQuote();
            $quote->getPayment()->setMethod('mercado_pago_custom');
            $order = $this->createOrderByPaymentWithQuote($payment);
        }

        if (!$order->getIncrementId()) {
            throw new \Exception(__("Sorry, we can't create a order with external reference #%1", $orderIncrementalId));
        }

        $this->paymentNotification->updateStatusOrderByPayment($payment);

        $session->setLastSuccessQuoteId($payment['metadata']['quote_id']);
        $session->setLastQuoteId($payment['metadata']['quote_id']);
        $session->setLastOrderId($payment['external_reference']);
        $session->setLastRealOrderId($payment['external_reference']);
    }

    /**
     * @param $paymentId
     * @return int|mixed
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    protected function createOrderByPaymentWithQuote($payment)
    {
        $quoteId = $payment['metadata']['quote_id'];

        $quote = $this->quoteRepository->get($quoteId);
        $quote->getPayment()->importData(['method' => 'mercadopago_basic']);

        $orderId = $this->quoteManagement->placeOrder($quote->getId());

        return $this->loadOrderById($orderId);
    }

    /**
     * @param $incrementalId
     * @return OrderInterface
     */
    public function loadOrderByIncrementalId($incrementalId)
    {
        return $this->order->loadByIncrementId($incrementalId);
    }

    /**
     * @param $orderId
     * @return OrderInterface
     */
    public function loadOrderById($orderId)
    {
        return $this->order->loadByAttribute('entity_id', $orderId);
    }

    /**
     * @param $merchantOrderId
     * @return mixed
     * @throws LocalizedException
     * @throws \Exception
     */
    protected function loadMerchantOrder($merchantOrderId)
    {
        $response = $this->getMercadoPagoInstance()->get_merchant_order($merchantOrderId);
        if (!empty($response['response']) && $response['status'] < 300) {
            return $response['response'];
        }

        throw new \Exception(__('Merchant Order not found or is an notification invalid type.'));
    }

    /**
     * @param $preferenceId
     * @return mixed
     * @throws LocalizedException
     * @throws \Exception
     */
    protected function loadPreference($preferenceId)
    {
        $response = $this->getMercadoPagoInstance()->get_preference($preferenceId);
        if (!empty($response['response']) && $response['status'] < 300) {
            return $response['response'];
        }

        throw new \Exception(__('Preference #%1 not found!', $preferenceId));
    }

    /**
     * @param $paymentId
     * @return mixed
     * @throws LocalizedException
     * @throws \Exception
     */
    protected function loadPayment($paymentId)
    {
        $response = $this->getMercadoPagoInstance()->get("/v1/payments/{$paymentId}");
        if (!empty($response['response']) && $response['status'] < 300) {
            return $response['response'];
        }

        throw new \Exception(__('Payment #%1 not found!', $paymentId));
    }

    /**
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    protected function buildPreferenceArray()
    {
        $quote = $this->getQuote();
        $preference = $this->getPreference();
        $siteId = $preference['metadata']['site'];
        $customer = $this->getCustomer();
        $quote->reserveOrderId();

        $preference['external_reference'] = $quote->getReservedOrderId();
        $preference['items'] = $this->getItems($quote, $siteId);
        $preference['payer'] = $this->getPayer($quote, $customer);

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

        $preference['shipments']['cost'] = $this->getPrice($quote->getShippingAddress()->getShippingAmount(), $siteId);
        $preference['metadata']['test_mode'] = $this->isTestMode($preference['payer']);
        $preference['metadata']['quote_id'] = $quote->getId();

        if ($this->getGatewayMode()) {
            $preference['gateway_mode'] = ['gateway'];
        }

        return $preference;
    }

    /**
     * @return array
     */
    protected function getPreference()
    {
        $this->version->afterLoad();

        return [
            'items' => [],
            'payer' => [],
            'back_urls' => [
                'success' => $this->urlBuilder->getUrl(self::SUCCESS_PATH),
                'pending' => $this->urlBuilder->getUrl(self::SUCCESS_PATH),
                'failure' => $this->urlBuilder->getUrl(self::FAILURE_PATH),
            ],
            'payment_methods' => [
                'excluded_payment_methods' => $this->getExcludedPaymentMethods(),
                'excluded_payment_types' => [],
                'installments' => $this->getMaxInstallments(),
            ],
            'shipments' => [
                'mode' => 'not_specified',
                'cost' => 0.00,
            ],
            'notification_url' => $this->urlBuilder->getUrl(self::NOTIFICATION_PATH),
            'statement_descriptor' => $this->getStateDescriptor(),
            'external_reference' => '',
            'binary_mode' => $this->getBinaryMode(),
            'purpose' => self::PURPOSE_WALLET_PURCHASE,
            'metadata' => [
                'site' => $this->getSiteId(),
                'platform' => 'Magento2',
                'platform_version' => $this->productMetadata->getVersion(),
                'module_version' => $this->version->getValue(),
                'sponsor_id' => $this->getSiteId(),
                'test_mode' => '',
                'quote_id' => '',
                'checkout' => 'pro',
                'checkout_type' => 'wallet_button'
            ]
        ];
    }

    /**
     * @return array
     */
    protected function getExcludedPaymentMethods()
    {
        $excluded = [];
        $configExcludedPaymentMethods = explode(
            ',',
            $this->getConfig(ConfigData::PATH_CUSTOM_EXCLUDE_PAYMENT_METHODS)
        );

        foreach ($configExcludedPaymentMethods as $paymentMethod) {
            $excluded[] = ['id' => $paymentMethod];
        }

        return $excluded;
    }

    /**
     * @param $path
     * @param string $scopeType
     * @return mixed
     */
    protected function getConfig($path, $scopeType = ScopeInterface::SCOPE_STORE)
    {
        return $this->scopeConfig->getValue($path, $scopeType);
    }

    /**
     * @return int
     */
    protected function getMaxInstallments()
    {
        return (int) $this->getConfig(ConfigData::PATH_BASIC_MAX_INSTALLMENTS);
    }

    /**
     * @return mixed
     */
    protected function getStateDescriptor()
    {
        return $this->getConfig(ConfigData::PATH_CUSTOM_STATEMENT_DESCRIPTOR);
    }

    /**
     * @return bool
     */
    protected function getBinaryMode()
    {
        $value = $this->getConfig(ConfigData::PATH_CUSTOM_BINARY_MODE);
        return $value ? true : false;
    }

    /**
     * @return false|string|string[]
     */
    protected function getSiteId()
    {
        return mb_strtoupper($this->getConfig(ConfigData::PATH_SITE_ID));
    }

    /**
     * @return CartInterface|ModelQuote
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    protected function getQuote()
    {
        return $this->checkoutSession->getQuote();
    }

    /**
     * @return DataCustomerInterface|Customer|ExtensibleDataInterface
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    protected function getCustomer()
    {
        return $this->getQuote()->getCustomer();
    }

    /**
     * @param Quote $quote
     * @param $siteId
     * @return array
     */
    protected function getItems(Quote $quote, $siteId)
    {
        $items = [];
        $categoryId = $this->getConfig(ConfigData::PATH_ADVANCED_CATEGORY);
        foreach ($quote->getAllVisibleItems() as $item) {
            $items[] = $this->getItem($item, $categoryId, $siteId);
        }

        return $items;
    }

    /**
     * @param Item $item
     * @param $categoryId
     * @param $siteId
     * @return array
     */
    protected function getItem(Item $item, $categoryId, $siteId)
    {
        $product = $item->getProduct();
        $image = $this->helperImage->init($product, 'image');

        return [
            'id' => $item->getSku(),
            'title' => $product->getName(),
            'description' => $product->getName(),
            'picture_url' => $image->getUrl(),
            'category_id' => $categoryId,
            'quantity' => (int) number_format($item->getQty(), 0, '.', ''),
            'unit_price' => $this->getPrice($item->getPrice(), $siteId),
        ];
    }

    /**
     * @param $price
     * @param $siteId
     * @return float|int
     */
    protected function getPrice($price, $siteId)
    {
        $amount = (float) number_format($price, 2, '.', '');

        if (in_array($siteId, self::COUNTRIES_WITH_INTEGER_PRICE, true)) {
            return (int) $amount;
        }

        return $amount;
    }

    /**
     * @param Quote $quote
     * @param CustomerInterface $customer
     * @return array
     */
    protected function getPayer(Quote $quote, CustomerInterface $customer)
    {
        $billing = $quote->getBillingAddress();
        $shipping = $quote->getShippingAddress();
        $data = $customer->getId() ? $customer : $billing;
        $createdAt = $customer->getId() ? strtotime($customer->getCreatedAt()) : time();
        $email = $data->getEmail() ? $data->getEmail() : $shipping->getEmail();

        return [
            'email' => htmlentities($email),
            'name' => htmlentities($data->getFirstname()),
            'surname' => htmlentities($data->getLastname()),
            'date_created' => date('c', $createdAt),
            'address' => [
                'zip_code' => $billing->getPostcode(),
                'street_name' => sprintf(
                    '%s - %s - %s - %s',
                    implode(', ', $billing->getStreet()),
                    $billing->getCity(),
                    $billing->getRegion(),
                    $billing->getCountry()
                ),
                'street_number' => ''
            ]
        ];
    }

    /**
     * @param $payer
     * @return bool
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
    }

    /**
     * @return int|null
     */
    protected function getSponsorId()
    {
        $sponsorId = $this->getConfig(ConfigData::PATH_SPONSOR_ID);

        if (!empty($sponsorId)) {
            return (int) $sponsorId;
        }

        return null;
    }

    /**
     * @return bool
     */
    protected function getGatewayMode()
    {
        $value = $this->getConfig(ConfigData::PATH_CUSTOM_GATEWAY_MODE);
        return $value ? true : false;
    }

    /**
     * @return Api
     * @throws LocalizedException
     */
    public function getMercadoPagoInstance()
    {
        return $this->helperData->getApiInstance($this->getAccessToken());
    }

    /**
     * @return mixed
     */
    protected function getAccessToken()
    {
        return $this->getConfig(ConfigData::PATH_ACCESS_TOKEN);
    }
}
