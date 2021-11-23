<?php

namespace MercadoPago\Test\Unit\Model;

use MercadoPago\Core\Helper\ConfigData;
use MercadoPago\Core\Model\CustomConfigProvider;
use MercadoPago\Test\Unit\Constants\ConfigProviderConstants;
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
    private $coreHelper;

    /**
     * @var MockObject
     */
    private $request;

    /**
     * @var MockObject
     */
    private $context;

    /**
     * @var MockObject
     */
    private $assetRepo;

    /**
     * @var MockObject
     */
    private $storeManager;

    /**
     * @var MockObject
     */
    private $checkoutSession;

    /**
     * @var MockObject
     */
    private $paymentHelper;

    /**
     * @var MockObject
     */
    private $scopeConfig;

    /**
     * @var MockObject
     */
    private $productMetadata;

    /**
     * @var MockObject
     */
    private $abstractMethod;

    /**
     * @var MockObject
     */
    private $storeInterface;
    /**
     * @var MockObject
     */
    private $quote;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $objectManagerHelper = new ObjectManager($this);
        $className = CustomConfigProvider::class;
        $arguments = $objectManagerHelper->getConstructArguments($className);

        $this->paymentHelper = $arguments['paymentHelper'];
        $this->coreHelper = $arguments['coreHelper'];
        $this->checkoutSession = $arguments['checkoutSession'];
        $this->assetRepo = $arguments['assetRepo'];
        $this->storeManager = $arguments['storeManager'];
        $this->scopeConfig = $arguments['scopeConfig'];
        $this->productMetadata = $arguments['productMetadata'];
        
        $this->abstractMethod = $this->getMockBuilder(AbstractMethod::class)
            ->setMethods(['isAvailable', 'getCustomerAndCards', 'getConfigData'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        
        $this->paymentHelper->expects($this->any())
            ->method('getMethodInstance')
            ->willReturn($this->abstractMethod);
        
        $this->storeInterface = $this->getMockBuilder(StoreInterface::class)
            ->setMethods(['getBaseUrl'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->storeManager->expects($this->any())
            ->method('getStore')
            ->willReturn($this->storeInterface);
        
        $this->quote = $this->getMockBuilder(Quote::class)
            ->setMethods(['getGrandTotal'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->checkoutSession->expects($this->any())
            ->method('getQuote')
            ->willReturn($this->quote);
        
        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->setMethods(['getRouteName'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        
        $this->context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $arguments['context'] = $this->context;
        
        $this->context->expects($this->any())
            ->method('getRequest')
            ->willReturn($this->request);

        $this->customConfigProvider = $objectManagerHelper->getObject($className, $arguments);
    }

    public function testGetConfig_successfulExecution_returnArray(): void
    {
        $this->abstractMethod->expects($this->once())
            ->method('isAvailable')
            ->willReturn(true);

        $this->abstractMethod->expects($this->once())
            ->method('getCustomerAndCards')
            ->willReturn('customer_and_cards');

        $this->abstractMethod->expects($this->once())
            ->method('getConfigData')
            ->willReturn('success_url');

        $this->scopeConfig->expects($this->any())
            ->method('getValue')
            ->willReturnArgument(0);

        $this->assetRepo->expects($this->any())
            ->method('getUrl')
            ->willReturnArgument(0);

        $this->storeInterface->expects($this->any())
            ->method('getBaseUrl')
            ->willReturn('base_url');

        $this->quote->expects($this->any())
            ->method('getGrandTotal')
            ->willReturn('grand_total');

        $this->request->expects($this->once())
            ->method('getRouteName')
            ->willReturn('route_name');

        $this->coreHelper->expects($this->once())
            ->method('getModuleversion')
            ->willReturn('module_version');

        $this->coreHelper->expects($this->once())
            ->method('getWalletButtonLink')
            ->willReturn('wallet_button_link');

        $this->coreHelper->expects($this->once())
            ->method('getFingerPrintLink')
            ->willReturn('fingerprint_link');

        $this->coreHelper->expects($this->once())
            ->method('getMercadoPagoPaymentMethods')
            ->willReturn(null);

        $this->productMetadata->expects($this->once())
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
        $this->abstractMethod->expects($this->once())
            ->method('isAvailable')
            ->willReturn(false);

        $this->assertEquals([], $this->customConfigProvider->getConfig());
    }

    public function testGetPaymentMethods_successExecution_returnCards(): void
    {
        $this->scopeConfig->expects($this->any())
            ->method('getValue')
            ->willReturnArgument(0);

        $this->coreHelper->expects($this->once())
            ->method('getMercadoPagoPaymentMethods')
            ->willReturn(ConfigProviderConstants::PAYMENT_METHODS);

        $expectedOutput = [
            0 => ConfigProviderConstants::PAYMENT_METHODS['response'][0],
            1 => ConfigProviderConstants::PAYMENT_METHODS['response'][1]
        ];

        $this->assertEquals($expectedOutput, $this->customConfigProvider->getPaymentMethods());
    }
}
