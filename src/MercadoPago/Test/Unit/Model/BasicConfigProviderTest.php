<?php

namespace MercadoPago\Test\Unit\Helper;

use MercadoPago\Core\Helper\ConfigData;
use MercadoPago\Core\Lib\Api;
use MercadoPago\Core\Model\BasicConfigProvider;
use MercadoPago\Test\Unit\Constants\ConfigProviderConstants;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Store\Model\ScopeInterface;

class BasicConfigProviderTest extends TestCase
{
    /**
     * @var BasicConfigProvider
     */
    private $basicConfigProvider;

    /**
     * @var MockObject
     */
    private $coreHelper;

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
    private $abstractMethodMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $objectManagerHelper = new ObjectManager($this);
        $className = BasicConfigProvider::class;
        $arguments = $objectManagerHelper->getConstructArguments($className);

        $this->context = $arguments['context'];
        $this->coreHelper = $arguments['coreHelper'];
        $this->assetRepo = $arguments['assetRepo'];
        $this->checkoutSession = $arguments['checkoutSession'];
        $this->paymentHelper = $arguments['paymentHelper'];
        $this->scopeConfig = $arguments['scopeConfig'];
        $this->productMetadata = $arguments['productMetadata'];

        $this->abstractMethodMock = $this->getMockBuilder(AbstractMethod::class)
            ->setMethods(['isAvailable'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->paymentHelper
            ->expects($this->any())
            ->method('getMethodInstance')
            ->willReturn($this->abstractMethodMock);

        $this->basicConfigProvider = $objectManagerHelper->getObject($className, $arguments);
    }

    public function testGetConfig_successfulExecution_returnArray(): void
    {
        $this->abstractMethodMock->expects($this->once())
            ->method('isAvailable')
            ->willReturn(true);

        $this->scopeConfig->expects($this->any())
            ->method('getValue')
            ->willReturnArgument(0);

        $this->assetRepo->expects($this->any())
            ->method('getUrl')
            ->willReturnArgument(0);

        $this->coreHelper->expects($this->once())
            ->method('getModuleversion')
            ->willReturn('module_version');

        $this->coreHelper->expects($this->once())
            ->method('getFingerPrintLink')
            ->willReturn('fingerprint_link');

        $this->productMetadata->expects($this->once())
            ->method('getVersion')
            ->willReturn('magento2');

        $expectedReturn = [
            'payment' => [
                \MercadoPago\Core\Model\Basic\Payment::CODE => [
                    'active' => 'payment/mercadopago_basic/active',
                    'logEnabled' => 'payment/mercadopago/logs',
                    'max_installments' => 'payment/mercadopago_basic/max_installments',
                    'auto_return' => 'payment/mercadopago_basic/auto_return',
                    'exclude_payments' => 'payment/mercadopago_basic/excluded_payment_methods',
                    'order_status' => 'payment/mercadopago_basic/order_status',
                    'logoUrl' => 'MercadoPago_Core::images/mp_logo.png',
                    'actionUrl' => null,
                    'banner_info' => null,
                    'loading_gif' => 'MercadoPago_Core::images/loading.gif',
                    'redirect_image' => 'MercadoPago_Core::images/redirect_checkout.png',
                    'module_version' => 'module_version',
                    'platform_version' => 'magento2',
                    'mercadopago_mini' => 'MercadoPago_Core::images/mercado-pago-mini.png',
                    'fingerprint_link' => 'fingerprint_link',
                ],
            ],
        ];

        $this->assertEquals($expectedReturn, $this->basicConfigProvider->getConfig());
    }

    public function testGetConfig_exceptionExecution_returnEmpty(): void
    {
        $this->abstractMethodMock->expects($this->once())
            ->method('isAvailable')
            ->will($this->throwException(new \Exception()));

        $this->assertEquals([], $this->basicConfigProvider->getConfig());
    }

    public function testGetConfig_methodInstanceNotAvailable_returnEmpty(): void
    {
        $this->abstractMethodMock->expects($this->once())
            ->method('isAvailable')
            ->willReturn(false);

        $this->assertEquals([], $this->basicConfigProvider->getConfig());
    }

    public function testMakeBannerCheckout_successExecution_returnArray(): void
    {
        $valueMap = [
            [ConfigData::PATH_ACCESS_TOKEN, ScopeInterface::SCOPE_STORE, null, 'some_access_token'],
            [ConfigData::PATH_BASIC_MAX_INSTALLMENTS, ScopeInterface::SCOPE_STORE, null, 2],
            [ConfigData::PATH_BASIC_EXCLUDE_PAYMENT_METHODS, ScopeInterface::SCOPE_STORE, null, '']
        ];

        $this->scopeConfig
            ->method('getValue')
            ->will($this->returnValueMap($valueMap));

        $this->coreHelper->expects($this->once())
            ->method('getMercadoPagoPaymentMethods')
            ->willReturn(ConfigProviderConstants::PAYMENT_METHODS);

        $expectedOutput = [
            "debit" => 1,
            "credit" => 1,
            "ticket" => 1,
            "installments" => 2,
            "checkout_methods" => ConfigProviderConstants::PAYMENT_METHODS['response'],
        ];

        $this->assertEquals($expectedOutput, $this->basicConfigProvider->makeBannerCheckout());
    }

    public function testMakeBannerCheckout_successExcludeMethod_returnArray(): void
    {
        $valueMap = [
            [ConfigData::PATH_ACCESS_TOKEN, ScopeInterface::SCOPE_STORE, null, 'some_access_token'],
            [ConfigData::PATH_BASIC_MAX_INSTALLMENTS, ScopeInterface::SCOPE_STORE, null, 1],
            [ConfigData::PATH_BASIC_EXCLUDE_PAYMENT_METHODS, ScopeInterface::SCOPE_STORE, null, 'paycash,amex']
        ];

        
        $this->scopeConfig
            ->method('getValue')
            ->will($this->returnValueMap($valueMap));
        
        echo "\n";
        echo $this->scopeConfig->getValue(ConfigData::PATH_ACCESS_TOKEN, ScopeInterface::SCOPE_STORE);
        echo "\n";
        echo $this->scopeConfig->getValue(ConfigData::PATH_BASIC_MAX_INSTALLMENTS, ScopeInterface::SCOPE_STORE);
        echo "\n";
        echo $this->scopeConfig->getValue(ConfigData::PATH_BASIC_EXCLUDE_PAYMENT_METHODS, ScopeInterface::SCOPE_STORE);
        echo "\n";

        $this->coreHelper->expects($this->once())
            ->method('getMercadoPagoPaymentMethods')
            ->willReturn(ConfigProviderConstants::PAYMENT_METHODS);

        $expectedOutput = [
            "debit" => 1,
            "credit" => 0,
            "ticket" => 0,
            "installments" => 1,
            "checkout_methods" => [
                0 => ConfigProviderConstants::PAYMENT_METHODS['response'][0]
            ],
        ];

        $this->assertEquals($expectedOutput, $this->basicConfigProvider->makeBannerCheckout());
    }

    public function testMakeBannerCheckout_exceptionExecution_returnNull(): void
    {
        $this->coreHelper->expects($this->once())
            ->method('getMercadoPagoPaymentMethods')
            ->will($this->throwException(new \Exception()));

            $this->assertEquals(null, $this->basicConfigProvider->makeBannerCheckout());
    }
}
