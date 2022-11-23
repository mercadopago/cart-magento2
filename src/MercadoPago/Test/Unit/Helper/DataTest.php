<?php

namespace MercadoPago\Test\Unit\Helper;

use MercadoPago\Core\Helper\Data;
use MercadoPago\Core\Lib\Api;
use MercadoPago\Test\Unit\Mock\PaymentResponseMock;
use MercadoPago\Test\Unit\Mock\PaymentMethodsConfigMock;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class DataTest extends TestCase
{
    /**
     * @var Data
     */
    private $data;

    /**
     * @var MockObject
     */
    private $messageInterfaceMock;

    /**
     * @var MockObject
     */
    private $mpCacheMock;

    /**
     * @var MockObject
     */
    private $contextMock;

    /**
     * @var LayoutFactory|MockObject
     */
    private $layoutFactoryMock;

    /**
     * @var MockObject
     */
    private $paymentMethodFactoryMock;

    /**
     * @var MockObject
     */
    private $appEmulationMock;

    /**
     * @var MockObject
     */
    private $paymentConfigMock;

    /**
     * @var MockObject
     */
    private $initialConfigMock;

    /**
     * @var MockObject
     */
    private $loggerMock;

    /**
     * @var MockObject
     */
    private $statusFactoryMock;

    /**
     * @var MockObject
     */
    private $orderFactoryMock;

    /**
     * @var MockObject
     */
    private $switcherMock;

    /**
     * @var MockObject
     */
    private $composerInformationMock;

    /**
     * @var MockObject
     */
    private $moduleResourceMock;

    /**
     * @var MockObject
     */
    private $apiMock;

    /**
     * @var MockObject
     */
    private $scopeConfigMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $objectManagerHelper = new ObjectManager($this);
        $className = Data::class;
        $arguments = $objectManagerHelper->getConstructArguments($className);

        $this->contextMock = $arguments['context'];
        $this->layoutFactoryMock = $arguments['layoutFactory'];
        $this->messageInterfaceMock = $arguments['messageInterface'];
        $this->mpCacheMock = $arguments['mpCache'];
        $this->paymentMethodFactoryMock = $arguments['paymentMethodFactory'];
        $this->appEmulationMock = $arguments['appEmulation'];
        $this->paymentConfigMock = $arguments['paymentConfig'];
        $this->initialConfigMock = $arguments['initialConfig'];
        $this->loggerMock = $arguments['logger'];
        $this->statusFactoryMock = $arguments['statusFactory'];
        $this->orderFactoryMock = $arguments['orderFactory'];
        $this->switcherMock = $arguments['switcher'];
        $this->composerInformationMock = $arguments['composerInformation'];
        $this->moduleResourceMock = $arguments['moduleResource'];
        $this->apiMock = $arguments['api'];
        $this->scopeConfigMock = $arguments['scopeConfig'];

        $this->data = $objectManagerHelper->getObject($className, $arguments);
    }

    public function testGetMercadoPagoPaymentMethods_successResponse_returnArrayWithPaymentPlaces(): void
    {
        $this->scopeConfigMock->expects($this->any())
        ->method('getValue')
        ->withConsecutive(['payment/mercadopago/public_key'], ['payment/mercadopago/access_token'])
        ->willReturnOnConsecutiveCalls(PaymentResponseMock::KEY_MOCK, PaymentResponseMock::TOKEN_MOCK);

        $this->apiMock->expects($this->any())
        ->method('validate_public_key')
        ->with(PaymentResponseMock::KEY_MOCK)
        ->willReturn(PaymentResponseMock::KEY_RESPONSE_MOCK);

        $this->apiMock->expects($this->any())
        ->method('validade_access_token')
        ->with(PaymentResponseMock::TOKEN_MOCK)
        ->willReturn(PaymentResponseMock::TOKEN_RESPONSE_MOCK);

        $this->apiMock->expects($this->any())
        ->method('get_payment_methods')
        ->with(PaymentResponseMock::KEY_MOCK)
        ->willReturn(PaymentResponseMock::RESPONSE_PAYMENT_METHODS_SUCCESS_WITH_PAYMENT_PLACES);

        $this->assertEquals(PaymentResponseMock::RESPONSE_PAYMENT_METHODS_SUCCESS_WITH_PAYMENT_PLACES, $this->data->getMercadoPagoPaymentMethods());
    }

    public function testGetMercadoPagoPaymentMethods_exception_returnEmpty(): void
    {
        $this->scopeConfigMock->expects($this->any())
        ->method('getValue')
        ->withConsecutive(['payment/mercadopago/public_key'], ['payment/mercadopago/access_token'])
        ->willReturnOnConsecutiveCalls(PaymentResponseMock::KEY_MOCK, PaymentResponseMock::TOKEN_MOCK);

        $this->apiMock->expects($this->any())
        ->method('validate_public_key')
        ->with(PaymentResponseMock::KEY_RESPONSE_MOCK)
        ->willReturn();

        $this->apiMock->expects($this->any())
        ->method('validade_access_token')
        ->with(PaymentResponseMock::TOKEN_RESPONSE_MOCK)
        ->willReturn();

        $this->apiMock->expects($this->any())
        ->method('get_payment_methods')
        ->with(PaymentResponseMock::KEY_MOCK)
        ->willReturn(null);

        $this->assertEquals([], $this->data->getMercadoPagoPaymentMethods());
    }
}
