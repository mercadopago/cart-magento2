<?php

namespace MercadoPago\Test\Unit\Model;

use MercadoPago\Core\Model\System\Config\Source\PaymentMethods\PaymentMethodsTicket;
use MercadoPago\Core\Helper\ConfigData;
use MercadoPago\Test\Unit\Constants\Config;
use MercadoPago\Test\Unit\Mock\PaymentResponseMock;
use MercadoPago\Test\Unit\Mock\PaymentMethodsConfigMock;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class PaymentMethodsTicketTest extends TestCase
{
    /**
     * @var PaymentMethodsTicket
     */
    private $paymentMethodsTicket;

    /**
     * @var MockObject
     */
    private $scopeConfigMock;

    /**
     * @var MockObject
     */
    private $coreHelperMock;

    /**
     * @var MockObject
     */
    private $switcherMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $objectManagerHelper = new ObjectManager($this);
        $className = PaymentMethodsTicket::class;
        $arguments = $objectManagerHelper->getConstructArguments($className);
        $this->scopeConfigMock = $arguments['scopeConfig'];
        $this->coreHelperMock = $arguments['coreHelper'];
        $this->switcherMock = $arguments['switcher'];

        $this->paymentMethodsTicket = $objectManagerHelper->getObject($className, $arguments);
    }

    public function testToOptionArray_success_returnArrayWithoutMethods(): void
    {
        $this->scopeConfigMock->expects(self::any())
        ->method('getValue')
        ->willReturn('');

        $this->assertEquals(PaymentMethodsConfigMock::EMPTY_PAYMENT_METHODS_CONFIG, $this->paymentMethodsTicket->toOptionArray());
    }

    public function testToOptionArray_success_returnArrayWithMethods(): void
    {
        $this->scopeConfigMock->expects(self::any())
        ->method('getValue')
        ->willReturn('APP_USR-00000000000-000000-000000-0000000000');

        $this->coreHelperMock->expects(self::any())
        ->method('getMercadoPagoPaymentMethods')
        ->with('APP_USR-00000000000-000000-000000-0000000000')
        ->willReturn(PaymentResponseMock::RESPONSE_PAYMENT_METHODS_SUCCESS_WITH_PAYMENT_PLACES);

        $this->assertEquals(PaymentMethodsConfigMock::PAYMENT_METHODS_CONFIG_SUCCESS, $this->paymentMethodsTicket->toOptionArray());
    }
}