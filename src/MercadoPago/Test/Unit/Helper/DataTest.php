<?php

namespace MercadoPago\Test\Unit\Helper;

use MercadoPago\Core\Helper\Data;
use MercadoPago\Core\Lib\Api;
use MercadoPago\Test\Unit\Constants\Config;
use MercadoPago\Test\Unit\Constants\Response;
use MercadoPago\Test\Unit\Constants\PaymentMethods;
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
        $this->api->expects($this->once())
        ->method('get')
        ->with(PaymentMethods::PAYMENT_METHODS_URI)
        ->willReturn(Response::RESPONSE_PAYMENT_METHODS_SUCCESS);

        $this->assertEquals(Response::RESPONSE_PAYMENT_METHODS_SUCCESS_WITH_PAY_PLACES, $this->helper->getMercadoPagoPaymentMethods(Config::ACCESS_TOKEN));
    }

    public function testGetMercadoPagoPaymentMethods_exception_returnEmpty(): void
    {
        $this->api->expects($this->once())
        ->method('get')
        ->with(PaymentMethods::PAYMENT_METHODS_URI)
        ->willReturn(null);

        $this->assertEquals([], $this->helper->getMercadoPagoPaymentMethods(Config::ACCESS_TOKEN));
    }
}