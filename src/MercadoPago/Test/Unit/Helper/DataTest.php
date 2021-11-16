<?php

namespace MercadoPago\Test\Unit\Helper;

use MercadoPago\Core\Helper\Data;
use MercadoPago\Core\Lib\Api;
use MercadoPago\Test\Unit\Constants\Response;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class DataTest extends TestCase
{
    /**
     * @var Data
     */
    private $helper;

    /**
     * @var MockObject
     */
    private $messageInterface;

    /**
     * @var MockObject
     */
    private $mpCache;

    /**
     * @var MockObject
     */
    private $context;

    /**
     * @var LayoutFactory|MockObject
     */
    private $layoutFactory;

    /**
     * @var MockObject
     */
    private $paymentMethodFactory;
    
    /**
     * @var MockObject
     */
    private $appEmulation;
    
    /**
     * @var MockObject
     */
    private $paymentConfig;

    /**
     * @var MockObject
     */
    private $initialConfig;

    /**
     * @var MockObject
     */
    private $logger;

    /**
     * @var MockObject
     */
    private $statusFactory;

    /**
     * @var MockObject
     */
    private $orderFactory;

    /**
     * @var MockObject
     */
    private $switcher;
    
    /**
     * @var MockObject
     */
    private $composerInformation;
    
    /**
     * @var MockObject
     */
    private $moduleResource;

    /**
     * @var MockObject
     */
    private $api;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $objectManagerHelper = new ObjectManager($this);
        $className = Data::class;
        $arguments = $objectManagerHelper->getConstructArguments($className);
        /** @var Context $context */
        $context = $arguments['context'];
        $this->scopeConfig = $context->getScopeConfig();
        $this->layoutFactory = $arguments['layoutFactory'];
        
        $this->messageInterface = $arguments['messageInterface'];
        $this->mpCache = $arguments['mpCache'];

        $this->paymentMethodFactory = $arguments['paymentMethodFactory'];
        $this->appEmulation = $arguments['appEmulation'];
        $this->paymentConfig = $arguments['paymentConfig'];
        $this->initialConfig = $arguments['initialConfig'];
        $this->logger = $arguments['logger'];
        $this->statusFactory = $arguments['statusFactory'];
        $this->orderFactory = $arguments['orderFactory'];
        $this->switcher = $arguments['switcher'];
        $this->composerInformation = $arguments['composerInformation'];
        $this->moduleResource = $arguments['moduleResource'];
        $this->api = $arguments['api'];

        $this->helper = $objectManagerHelper->getObject($className, $arguments);
    }

    public function testGetMercadoPagoPaymentMethods_successResponse_returnArrayWithPaymentPlaces(): void
    {
        list($accesstoken, $payment_methods, $expected_payment_methods, $uri) = [
            'APP_USR-00000000000-000000-000000-0000000000',
            Response::RESPONSE_PAYMENT_METHODS_SUCCESS,
            Response::RESPONSE_PAYMENT_METHODS_SUCCESS_WITH_PAY_PLACES,
            '/v1/payment_methods',
        ];

        $this->api->expects($this->once())
        ->method('get')
        ->with($uri)
        ->willReturn($payment_methods);

        $this->assertEquals($expected_payment_methods, $this->helper->getMercadoPagoPaymentMethods($accesstoken));
    }

    public function testGetMercadoPagoPaymentMethods_exception_returnEmpty(): void
    {
        list($accesstoken, $uri) = [
            'APP-ACCESSTOKEN-TEST',
            '/v1/payment_methods',
        ];

        $this->api->expects($this->once())
        ->method('get')
        ->with($uri)
        ->willReturn(null);

        $this->expectException(Exception::class);
        $response = $this->helper->getMercadoPagoPaymentMethods($accesstoken);
    }
}