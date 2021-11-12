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
        
        
        $this->helper = $objectManagerHelper->getObject($className, $arguments);
    }

    public function testGetMercadoPagoPaymentMethods(): void
    {
        list($accesstoken, $payment_methods, $uri) = [
            'APP-ACCESSTOKEN-TEST',
            Response::RESPONSE_PAYMENT_METHODS_SUCCESS,
            '/v1/payment_methods',
        ];

        $apiMock = $this->getMockBuilder(Api::class)
        ->disableOriginalConstructor()
        ->getMock();

        $apiMock
        ->method(
            'get'
        )->with(
            $uri
        )->willReturn(
            $payment_methods
        );

        $this->assertEquals($payment_meethods, $this->helper->getMercadoPagoPaymentMethods($accesstoken));
    }
}