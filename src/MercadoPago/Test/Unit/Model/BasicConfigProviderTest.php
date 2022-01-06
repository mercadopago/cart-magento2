<?php

namespace MercadoPago\Test\Unit\Model;

use MercadoPago\Core\Helper\ConfigData;
use MercadoPago\Core\Lib\Api;
use MercadoPago\Core\Model\BasicConfigProvider;
use MercadoPago\Test\Unit\Mock\PaymentResponseMock;
use MercadoPago\Test\Unit\Mock\PaymentMethodsConfigMock;
use Magento\Framework\App\Action\Context;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Store\Model\ScopeInterface;
use \Magento\Framework\UrlInterface;

class BasicConfigProviderTest extends TestCase
{
    /**
     * @var BasicConfigProvider
     */
    private $basicConfigProvider;

    /**
     * @var MockObject
     */
    private $coreHelperMock;

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
    private $urlMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $objectManagerHelper = new ObjectManager($this);
        $className = BasicConfigProvider::class;
        $arguments = $objectManagerHelper->getConstructArguments($className);

        $this->contextMock = $arguments['context'];
        $this->coreHelperMock = $arguments['coreHelper'];
        $this->assetRepoMock = $arguments['assetRepo'];
        $this->checkoutSessionMock = $arguments['checkoutSession'];
        $this->paymentHelperMock = $arguments['paymentHelper'];
        $this->scopeConfigMock = $arguments['scopeConfig'];
        $this->productMetadataMock = $arguments['productMetadata'];

        $this->abstractMethodMock = $this->getMockBuilder(AbstractMethod::class)
        ->setMethods(['isAvailable'])
        ->disableOriginalConstructor()
        ->getMockForAbstractClass();

        $this->paymentHelperMock
        ->expects($this->any())
        ->method('getMethodInstance')
         ->willReturn($this->abstractMethodMock);
        
        $this->urlMock = $this->getMockBuilder(UrlInterface::class)
        ->setMethods(['getUrl'])
        ->disableOriginalConstructor()
        ->getMockForAbstractClass();
        
        $this->contextMock = $this->getMockBuilder(Context::class)
        ->disableOriginalConstructor()
        ->getMock();
        
        $arguments['context'] = $this->contextMock;
        
        $this->contextMock->expects($this->any())
        ->method('getUrl')
        ->willReturn($this->urlMock);

        $this->basicConfigProvider = $objectManagerHelper->getObject($className, $arguments);
    }

    public function testGetConfig_successfulExecution_returnArray(): void
    {
        $this->abstractMethodMock->expects($this->once())
        ->method('isAvailable')
        ->willReturn(true);

        $this->scopeConfigMock->expects($this->any())
        ->method('getValue')
        ->willReturnArgument(0);

        $this->assetRepoMock->expects($this->any())
        ->method('getUrl')
        ->willReturnArgument(0);

        $this->urlMock->expects($this->once())
        ->method('getUrl')
        ->willReturn('action_url');

        $this->coreHelperMock->expects($this->once())
        ->method('getModuleversion')
        ->willReturn('module_version');

        $this->coreHelperMock->expects($this->once())
        ->method('getFingerPrintLink')
        ->willReturn('fingerprint_link');

        $this->productMetadataMock->expects($this->once())
        ->method('getVersion')
        ->willReturn('magento2');

        $expectedReturn = [
            'payment' => [
                \MercadoPago\Core\Model\Basic\Payment::CODE => [
                    'active' => ConfigData::PATH_BASIC_ACTIVE,
                    'logEnabled' => ConfigData::PATH_ADVANCED_LOG,
                    'max_installments' => ConfigData::PATH_BASIC_MAX_INSTALLMENTS,
                    'auto_return' => ConfigData::PATH_BASIC_AUTO_RETURN,
                    'exclude_payments' => ConfigData::PATH_BASIC_EXCLUDE_PAYMENT_METHODS,
                    'order_status' => ConfigData::PATH_BASIC_ORDER_STATUS,
                    'logoUrl' => 'MercadoPago_Core::images/mp_logo.png',
                    'actionUrl' => 'action_url',
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

        $this->scopeConfigMock
        ->method('getValue')
        ->will($this->returnValueMap($valueMap));

        $this->coreHelperMock->expects($this->once())
        ->method('getMercadoPagoPaymentMethods')
        ->willReturn(PaymentResponseMock::RESPONSE_PAYMENT_METHODS_CONFIG_PROVIDER);

        $expectedOutput = [
            "debit" => 1,
            "credit" => 1,
            "ticket" => 1,
            "installments" => 2,
            "checkout_methods" => PaymentResponseMock::RESPONSE_PAYMENT_METHODS_CONFIG_PROVIDER['response'],
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

        $this->scopeConfigMock
        ->method('getValue')
        ->will($this->returnValueMap($valueMap));

        $this->coreHelperMock->expects($this->once())
        ->method('getMercadoPagoPaymentMethods')
        ->willReturn(PaymentResponseMock::RESPONSE_PAYMENT_METHODS_CONFIG_PROVIDER);

        $expectedOutput = [
            "debit" => 1,
            "credit" => 0,
            "ticket" => 0,
            "installments" => 1,
            "checkout_methods" => [
                0 => PaymentResponseMock::RESPONSE_PAYMENT_METHODS_CONFIG_PROVIDER['response'][0]
            ],
        ];

        $this->assertEquals($expectedOutput, $this->basicConfigProvider->makeBannerCheckout());
    }

    public function testMakeBannerCheckout_exceptionExecution_returnNull(): void
    {
        $this->coreHelperMock->expects($this->once())
        ->method('getMercadoPagoPaymentMethods')
        ->will($this->throwException(new \Exception()));

        $this->assertEquals(null, $this->basicConfigProvider->makeBannerCheckout());
    }
}
