<?php

namespace MercadoPago\Test\Unit\Model;

use MercadoPago\Core\Model\System\Config\Source\PaymentMethods\PaymentMethodsTicket;
use MercadoPago\Core\Helper\ConfigData;
use MercadoPago\Test\Unit\Constants\Config;
use MercadoPago\Test\Unit\Constants\Response;
use MercadoPago\Test\Unit\Constants\PaymentMethods;
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
    private $helper;

    /**
     * @var MockObject
     */
    private $scopeConfig;

    /**
     * @var MockObject
     */
    private $coreHelper;

    /**
     * @var MockObject
     */
    private $switcher;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $objectManagerHelper = new ObjectManager($this);
        $className = PaymentMethodsTicket::class;
        $arguments = $objectManagerHelper->getConstructArguments($className);
        $this->scopeConfig = $arguments['scopeConfig'];
        $this->coreHelper = $arguments['coreHelper'];
        $this->switcher = $arguments['switcher'];

        $this->helper = $objectManagerHelper->getObject($className, $arguments);
    }

    public function testToOptionArray_success_returnArrayWithoutMethods(): void
    {
        $this->scopeConfig->expects(self::any())->method('getValue')
        ->with(ConfigData::PATH_ACCESS_TOKEN, ScopeInterface::SCOPE_WEBSITE, $this->switcher->getWebsiteId())
        ->willReturn('');

        $this->assertEquals(PaymentMethods::EMPTY_PAYMENT_METHODS, $this->helper->toOptionArray());
    }

    public function testToOptionArray_success_returnArrayWithMethods(): void
    {
        $this->scopeConfig->expects(self::any())->method('getValue')
        ->with(ConfigData::PATH_ACCESS_TOKEN, ScopeInterface::SCOPE_WEBSITE, $this->switcher->getWebsiteId())
        ->willReturn(Config::ACCESS_TOKEN);

        $this->coreHelper->expects(self::any())->method('getMercadoPagoPaymentMethods')
        ->with(Config::ACCESS_TOKEN)
        ->willReturn(Response::RESPONSE_PAYMENT_METHODS_SUCCESS_WITH_PAY_PLACES);

        $this->assertEquals(PaymentMethods::PAYMENT_METHODS_SUCCESS, $this->helper->toOptionArray());
    }
}