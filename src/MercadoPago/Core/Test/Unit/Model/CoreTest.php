<?php
/**
 * Created by PhpStorm.
 * User: Barbazul
 * Date: 6/3/2018
 * Time: 10:53 AM
 */

namespace MercadoPago\Core\Test\Unit\Model;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\UrlInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Address as OrderAddress;
use Magento\Sales\Model\OrderFactory;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use MercadoPago\Core\Helper\Data as CoreHelper;
use MercadoPago\Core\Model\Core;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as MockObject;

class CoreTest extends TestCase
{
    /**
     * @var Core
     */
    protected $model;

    /**
     * @var ObjectManager
     */
    protected $helper;

    /**
     * @var Session
     */
    protected $customerSession;

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var OrderFactory
     */
    protected $orderFactory;

    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    public function setUp()
    {
        $this->helper = new ObjectManager($this);
        $this->customerSession = $this->getCustomerSessionMock();
        $this->urlBuilder = $this->getUrlMock();
        $this->orderFactory = $this->getOrderFactoryMock();
        $this->checkoutSession = $this->getCheckoutSessionMock();
        $this->storeManager = $this->getStoreManagerMock();

        $coreHelper = $this->getMockBuilder(CoreHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['log'])
            ->getMock();

        $scopeConfig = $this->getMockBuilder(ScopeConfigInterface::class)->getMock();

        $this->model = $this->helper->getObject(
            Core::class,
            [
                'customerSession' => $this->customerSession,
                'urlBuilder' => $this->urlBuilder,
                'orderFactory' => $this->orderFactory,
                'checkoutSession' => $this->checkoutSession,
                'storeManager' => $this->storeManager,
                'coreHelper' => $coreHelper,
                'scopeConfig' => $scopeConfig
            ]
        );
    }

    public function testDefaultPreferenceStructure()
    {
        $preference = $this->model->makeDefaultPreferencePaymentV1();
        $this->assertInternalType('array', $preference);
        $this->assertArrayHasKey('notification_url', $preference);
        $this->assertArrayHasKey('description', $preference);
        $this->assertArrayHasKey('transaction_amount', $preference);
        $this->assertArrayHasKey('external_reference', $preference);
        $this->assertArrayHasKey('payer', $preference);
        $this->assertPayerStructure($preference);
        $this->assertArrayHasKey('additional_info', $preference);
        $this->assertAdditionalInfoStructure($preference);
    }

    public function testAmountRounds2DecimalsFromPaymentInfo()
    {
        $paymentInfo = ['transaction_amount' => 42.625];
        $preference = $this->model->makeDefaultPreferencePaymentV1($paymentInfo);
        $this->assertEquals(42.63, $preference['transaction_amount']);
    }

    public function testAmountRounds2DecimalsFromQuote()
    {
        $this->checkoutSession->getQuote()->setData('base_subtotal_with_discount', 42.625);
        $preference = $this->model->makeDefaultPreferencePaymentV1();

        $this->assertEquals(42.63, $preference['transaction_amount']);
    }

    /**
     * Create mock object for order model
     * @return MockObject
     */
    protected function getOrderMock()
    {
        $orderData = [
            'currency' => 'USD',
            'id' => 4,
            'increment_id' => '0000004'
        ];
        $orderMock = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'getBaseCurrencyCode',
                    'getIncrementId',
                    'getId',
                    'getBillingAddress',
                    'getShippingAddress',
                    'loadByIncrementId',
                    'getAllVisibleItems',
                    'canShip'
                ]
            )
            ->getMock();

        $billingAddress = $this->getMockBuilder(OrderAddress::class)
            ->disableOriginalConstructor()->getMock();

        $shippingAddress = $this->getMockBuilder(OrderAddress::class)
            ->disableOriginalConstructor()->getMock();

        $billingAddress->expects(self::any())->method('getFirstName')->willReturn('John');
        $billingAddress->expects(self::any())->method('getLastName')->willReturn('Doe');

        $orderMock->expects(self::any())->method('getBillingAddress')->willReturn($billingAddress);
        $orderMock->expects(self::any())->method('getShippingAddress')->willReturn($shippingAddress);

        $orderMock->expects(static::any())
            ->method('getIncrementId')
            ->willReturn($orderData['increment_id']);

        $orderMock->expects(static::any())
            ->method('getAllVisibleItems')
            ->willReturn([]);

        $orderMock->expects(static::any())
            ->method('canShip')
            ->willReturn(true);

        $orderMock->expects(static::any())->method('loadByIncrementId')->willReturnSelf();
        return $orderMock;
    }

    /**
     * @return MockObject
     */
    protected function getCustomerSessionMock(): MockObject
    {
        $session = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()->getMock();

        $customer = $this->getCustomerMock();

        $session->expects(static::once())
            ->method('getCustomer')
            ->willReturn($customer);

        return $session;
    }

    /**
     * @return MockObject
     */
    protected function getCustomerMock(): MockObject
    {
        return $this->getMockBuilder(Customer::class)
            ->disableOriginalConstructor()->getMock();
    }

    /**
     * @return MockObject|Quote
     */
    protected function getQuoteMock(): MockObject
    {
        $quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(['getBillingAddress', 'getShippingAddress'])
            ->getMock();

        $billingAddress = $this->getMockBuilder(Address::class)
            ->disableOriginalConstructor()->getMock();

        $billingAddress->expects(self::any())->method('getFirstName')->willReturn('John');
        $billingAddress->expects(self::any())->method('getLastName')->willReturn('Doe');

        $shippingAddress = $this->getMockBuilder(Address::class)
            ->disableOriginalConstructor()->getMock();

        $quote->expects(self::any())->method('getBillingAddress')->willReturn($billingAddress);
        $quote->expects(self::any())->method('getShippingAddress')->willReturn($shippingAddress);

        return $quote;
    }

    /**
     * @return MockObject
     */
    protected function getUrlMock(): MockObject
    {
        $url = $this->getMockBuilder(UrlInterface::class)->getMock();

        $url->expects(self::any())->method('getUrl')->willReturn('http://www.example.com/path/');

        return $url;
    }

    /**
     * @return MockObject
     */
    protected function getOrderFactoryMock(): MockObject
    {
        $orderFactory = $this->getMockBuilder(OrderFactory::class)->getMock();

        $orderFactory->expects(self::any())->method('create')->willReturn($this->getOrderMock());

        return $orderFactory;
    }

    /**
     * return MockObject
     */
    protected function getCheckoutSessionMock()
    {
        $checkoutSession = $this->getMockBuilder(CheckoutSession::class)
            ->disableOriginalConstructor()->getMock();

        $checkoutSession->expects(self::any())->method('getQuote')->willReturn($this->getQuoteMock());

        return $checkoutSession;
    }

    /**
     * @return MockObject
     */
    protected function getStoreManagerMock(): MockObject
    {
        $storeId = 27;

        $storeMock = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId', 'getBaseUrl'])
            ->getMock();

        $storeMock->expects(static::any())
            ->method('getId')
            ->willReturn($storeId);

        $storeMock->expects(static::once())
            ->method('getBaseUrl')
            ->willReturn('http://www.example.com/');

        $storeManagerMock = $this->getMockBuilder(StoreManagerInterface::class)
            ->getMockForAbstractClass();

        $storeManagerMock->expects(static::once())
            ->method('getStore')
            ->willReturn($storeMock);

        return $storeManagerMock;
    }

    /**
     * @param $preference
     */
    protected function assertPayerStructure($preference): void
    {
        $this->assertInternalType('array', $preference['payer']);
        $this->assertArrayHasKey('email', $preference['payer']);
    }

    /**
     * @param $preference
     */
    protected function assertAdditionalInfoStructure($preference): void
    {
        $this->assertInternalType('array', $preference['additional_info']);
        $this->assertArrayHasKey('items', $preference['additional_info']);
        $this->assertInternalType('array', $preference['additional_info']['items']);
        $this->assertArrayHasKey('payer', $preference['additional_info']);
        $this->assertInternalType('array', $preference['additional_info']['payer']);
        $this->assertArrayHasKey('first_name', $preference['additional_info']['payer']);
        $this->assertArrayHasKey('last_name', $preference['additional_info']['payer']);
        $this->assertArrayHasKey('address', $preference['additional_info']['payer']);
        $this->assertInternalType('array', $preference['additional_info']['payer']['address']);
        $this->assertArrayHasKey('zip_code', $preference['additional_info']['payer']['address']);
        $this->assertArrayHasKey('street_name', $preference['additional_info']['payer']['address']);
        $this->assertArrayHasKey('street_number', $preference['additional_info']['payer']['address']);
        $this->assertArrayHasKey('registration_date', $preference['additional_info']['payer']);
        $this->assertArrayHasKey('phone', $preference['additional_info']['payer']);
        $this->assertArrayHasKey('area_code', $preference['additional_info']['payer']['phone']);
        $this->assertArrayHasKey('number', $preference['additional_info']['payer']['phone']);
        $this->assertInternalType('array', $preference['additional_info']['payer']['phone']);
        $this->assertArrayHasKey('shipments', $preference['additional_info']);
        $this->assertInternalType('array', $preference['additional_info']['shipments']);
        $this->assertArrayHasKey('receiver_address', $preference['additional_info']['shipments']);
        $this->assertInternalType('array', $preference['additional_info']['shipments']['receiver_address']);
        $this->assertArrayHasKey('zip_code', $preference['additional_info']['shipments']['receiver_address']);
        $this->assertArrayHasKey('street_name', $preference['additional_info']['shipments']['receiver_address']);
        $this->assertArrayHasKey('street_number', $preference['additional_info']['shipments']['receiver_address']);
        $this->assertArrayHasKey('floor', $preference['additional_info']['shipments']['receiver_address']);
        $this->assertArrayHasKey('apartment', $preference['additional_info']['shipments']['receiver_address']);
    }
}
