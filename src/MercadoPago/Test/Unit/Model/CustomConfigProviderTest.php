<?php

namespace MercadoPago\Test\Unit\Model;

use MercadoPago\Core\Helper\ConfigData;
use MercadoPago\Core\Model\CustomConfigProvider;
use MercadoPago\Test\Unit\Mock\PaymentResponseMock;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Quote\Model\Quote;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Api\Data\StoreInterface;

class CustomConfigProviderTest extends TestCase
{
    /**
     * @var CustomConfigProvider
     */
    private $customConfigProvider;

    /**
     * @var MockObject
     */
    private $coreHelperMock;

    /**
     * @var MockObject
     */
    private $requestMock;

    /**
     * @var MockObject
     */
    private $contextMock;

    /**
     * @var MockObject
     */
    private $assetRepoMock;

    /**
     * @var MockObject
     */
    private $storeManagerMock;

    /**
     * @var MockObject
     */
    private $checkoutSessionMock;

    /**
     * @var MockObject
     */
    private $paymentHelperMock;

    /**
     * @var MockObject
     */
    private $scopeConfigMock;

    /**
     * @var MockObject
     */
    private $productMetadataMock;

    /**
     * @var MockObject
     */
    private $abstractMethodMock;

    /**
     * @var MockObject
     */
    private $storeInterfaceMock;
    /**
     * @var MockObject
     */
    private $quoteMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $objectManagerHelper = new ObjectManager($this);
        $className = CustomConfigProvider::class;
        $arguments = $objectManagerHelper->getConstructArguments($className);

        $this->paymentHelperMock = $arguments['paymentHelper'];
        $this->coreHelperMock = $arguments['coreHelper'];
        $this->checkoutSessionMock = $arguments['checkoutSession'];
        $this->assetRepoMock = $arguments['assetRepo'];
        $this->storeManagerMock = $arguments['storeManager'];
        $this->scopeConfigMock = $arguments['scopeConfig'];
        $this->productMetadataMock = $arguments['productMetadata'];
        
        $this->abstractMethodMock = $this->getMockBuilder(AbstractMethod::class)
        ->setMethods(['isAvailable', 'getCustomerAndCards', 'getConfigData'])
        ->disableOriginalConstructor()
        ->getMockForAbstractClass();
        
        $this->paymentHelperMock->expects($this->any())
        ->method('getMethodInstance')
        ->willReturn($this->abstractMethodMock);
        
        $this->storeInterfaceMock = $this->getMockBuilder(StoreInterface::class)
        ->setMethods(['getBaseUrl'])
        ->disableOriginalConstructor()
        ->getMockForAbstractClass();

        $this->storeManagerMock->expects($this->any())
        ->method('getStore')
        ->willReturn($this->storeInterfaceMock);
        
        $this->quoteMock = $this->getMockBuilder(Quote::class)
        ->setMethods(['getGrandTotal'])
        ->disableOriginalConstructor()
        ->getMock();

        $this->checkoutSessionMock->expects($this->any())
        ->method('getQuote')
        ->willReturn($this->quoteMock);
        
        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
        ->setMethods(['getRouteName'])
        ->disableOriginalConstructor()
        ->getMockForAbstractClass();
        
        $this->contextMock = $this->getMockBuilder(Context::class)
        ->disableOriginalConstructor()
        ->getMock();
        
        $arguments['context'] = $this->contextMock;
        
        $this->contextMock->expects($this->any())
        ->method('getRequest')
        ->willReturn($this->requestMock);

        $this->customConfigProvider = $objectManagerHelper->getObject($className, $arguments);
    }

    public function testGetConfig_successfulExecution_returnArray(): void
    {
        $this->abstractMethodMock->expects($this->once())
        ->method('isAvailable')
        ->willReturn(true);

        $this->abstractMethodMock->expects($this->once())
        ->method('getCustomerAndCards')
        ->willReturn('customer_and_cards');

        $this->abstractMethodMock->expects($this->once())
        ->method('getConfigData')
        ->willReturn('success_url');

        $this->scopeConfigMock->expects($this->any())
        ->method('getValue')
        ->willReturnArgument(0);

        $this->assetRepoMock->expects($this->any())
        ->method('getUrl')
        ->willReturnArgument(0);

        $this->storeInterfaceMock->expects($this->any())
        ->method('getBaseUrl')
        ->willReturn('base_url');

        $this->quoteMock->expects($this->any())
        ->method('getGrandTotal')
        ->willReturn('grand_total');

        $this->requestMock->expects($this->once())
        ->method('getRouteName')
        ->willReturn('route_name');

        $this->coreHelperMock->expects($this->once())
        ->method('getModuleversion')
        ->willReturn('module_version');

        $this->coreHelperMock->expects($this->once())
        ->method('getWalletButtonLink')
        ->willReturn('wallet_button_link');

        $this->coreHelperMock->expects($this->once())
        ->method('getFingerPrintLink')
        ->willReturn('fingerprint_link');

        $this->coreHelperMock->expects($this->once())
        ->method('getMercadoPagoPaymentMethods')
        ->willReturn(null);

        $this->productMetadataMock->expects($this->once())
        ->method('getVersion')
        ->willReturn('magento2');

        $expectedReturn = [
            'payment' => [
                \MercadoPago\Core\Model\Custom\Payment::CODE => [
                    'bannerUrl' => ConfigData::PATH_CUSTOM_BANNER,
                    'public_key' => ConfigData::PATH_PUBLIC_KEY,
                    'logEnabled' => ConfigData::PATH_ADVANCED_LOG,
                    'mp_gateway_mode' => ConfigData::PATH_CUSTOM_GATEWAY_MODE,
                    'mp_wallet_button' => ConfigData::PATH_CUSTOM_WALLET_BUTTON,
                    'country' => strtoupper(ConfigData::PATH_SITE_ID),
                    'route' => "route_name",
                    'logoUrl' => "MercadoPago_Core::images/mp_logo.png",
                    'minilogo' => "MercadoPago_Core::images/minilogo.png",
                    'gray_minilogo' => "MercadoPago_Core::images/gray_minilogo.png",
                    'base_url' => 'base_url',
                    'customer' => "customer_and_cards",
                    'grand_total' => 'grand_total',
                    'success_url' => "success_url",
                    'loading_gif' => 'MercadoPago_Core::images/loading.gif',
                    'text-choice' => __('Select'),
                    'text-currency' => __('$'),
                    'default-issuer' => __('Default issuer'),
                    'module_version' => "module_version",
                    'platform_version' => "magento2",
                    'text-installment' => __('Enter the card number'),
                    'wallet_button_link' => "wallet_button_link",
                    'payment_methods' => null,
                    'creditcard_mini' => "MercadoPago_Core::images/creditcard-mini.png",
                    'fingerprint_link' => 'fingerprint_link',
                ],
            ],
        ];

        $this->assertEquals($expectedReturn, $this->customConfigProvider->getConfig());
    }

    public function testGetConfig_methodInstanceNotAvailable_returnEmpty(): void
    {
        $this->abstractMethodMock->expects($this->once())
        ->method('isAvailable')
        ->willReturn(false);

        $this->assertEquals([], $this->customConfigProvider->getConfig());
    }

    public function testGetPaymentMethods_successExecution_returnCards(): void
    {
        $this->scopeConfigMock->expects($this->any())
        ->method('getValue')
        ->willReturnArgument(0);

        $this->coreHelperMock->expects($this->once())
        ->method('getMercadoPagoPaymentMethods')
        ->willReturn(PaymentResponseMock::RESPONSE_PAYMENT_METHODS_CONFIG_PROVIDER);

        $expectedOutput = [
            0 => PaymentResponseMock::RESPONSE_PAYMENT_METHODS_CONFIG_PROVIDER['response'][0],
            1 => PaymentResponseMock::RESPONSE_PAYMENT_METHODS_CONFIG_PROVIDER['response'][1]
        ];

        $this->assertEquals($expectedOutput, $this->customConfigProvider->getPaymentMethods());
    }
}
