<?php

namespace MercadoPago\Test\Unit\Block\Fieldset;

use MercadoPago\Core\Block\Adminhtml\System\Config\Fieldset\Payment;
use MercadoPago\Test\Unit\Mock\PaymentResponseMock;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\Data\Form\Element\AbstractElement;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class PaymentTest extends TestCase
{
    /**
     * @var Payment
     */
    private $payment;

    /**
     * @var MockObject
     */
    protected $contextMock;

    /**
     * @var MockObject
     */
    protected $authSessionMock;

    /**
     * @var MockObject
     */
    protected $jsHelperMock;

    /**
     * @var MockObject
     */
    protected $scopeConfigMock;

    /**
     * @var MockObject
     */
    protected $configResourceMock;

    /**
     * @var MockObject
     */
    protected $switcherMock;

    /**
     * @var MockObject
     */
    protected $dataMock;

    /**
     * @var MockObject
     */
    protected $coreHelperMock;

    /**
     * @var MockObject
     */
    protected $abstractElementMock;


    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $objectManagerHelper = new ObjectManager($this);
        $className = Payment::class;
        $arguments = $objectManagerHelper->getConstructArguments($className);

        $this->contextMock = $arguments['context'];
        $this->authSessionMock = $arguments['authSession'];
        $this->jsHelperMock = $arguments['jsHelper'];
        $this->scopeConfigMock = $arguments['scopeConfig'];
        $this->configResourceMock = $arguments['configResource'];
        $this->switcherMock = $arguments['switcher'];
        $this->dataMock = $arguments['data'];
        $this->coreHelperMock = $arguments['coreHelper'];

        $this->abstractElementMock = $this->getMockBuilder(AbstractElement::class)
        ->setMethods(['getId'])
        ->disableOriginalConstructor()
        ->getMockForAbstractClass();

        $this->payment = $objectManagerHelper->getObject($className, $arguments);
    }

    public function testHideBankTransfer_success_returnTrue(): void
    {
        $this->abstractElementMock
        ->expects($this->any())
        ->method('getId')
        ->willReturn('custom_checkout_bank_transfer');

        $this->scopeConfigMock->expects(self::any())
        ->method('getValue')
        ->willReturn('APP_USR-00000000000-000000-000000-0000000000');
        
        $this->coreHelperMock->expects(self::any())
        ->method('getMercadoPagoPaymentMethods')
        ->with('APP_USR-00000000000-000000-000000-0000000000')
        ->willReturn(PaymentResponseMock::RESPONSE_PAYMENT_METHODS_SUCCESS_MLB);

        $this->assertEquals("", $this->payment->render($this->abstractElementMock));
    }

    public function testHidePix_success_returnTrue(): void
    {
        $this->abstractElementMock
        ->expects($this->any())
        ->method('getId')
        ->willReturn('custom_checkout_pix');

        $this->scopeConfigMock->expects(self::any())
        ->method('getValue')
        ->willReturn('MLA');

        $this->assertEquals("", $this->payment->render($this->abstractElementMock));
    }

    public function testGetAvailableCheckoutsOptions_success_returnOptions(): void {
        $this->coreHelperMock
        ->expects($this->any())
        ->method('getAccessToken')
        ->willReturn('access_token');

        $this->coreHelperMock
        ->expects($this->any())
        ->method('getMercadoPagoPaymentMethods')
        ->willReturn(PaymentResponseMock::RESPONSE_PAYMENT_METHODS_SUCCESS);

        $checkoutOptions = $this->payment->getAvailableCheckoutOptions('access_token');

        $this->assertEquals($checkoutOptions, array(Payment::CHECKOUT_CUSTOM_CARD, Payment::CHECKOUT_CUSTOM_TICKET, Payment::CHECKOUT_CUSTOM_BANK_TRANSFER));
    }

    public function testGetAvailableCheckoutsOptions_success_returnOptionsWithPix(): void {
        $this->coreHelperMock
        ->expects($this->any())
        ->method('getAccessToken')
        ->willReturn('access_token');

        $this->coreHelperMock
        ->expects($this->any())
        ->method('getMercadoPagoPaymentMethods')
        ->willReturn(PaymentResponseMock::RESPONSE_PAYMENT_METHODS_SUCCESS_MLB);

        $checkoutOptions = $this->payment->getAvailableCheckoutOptions('access_token');

        $this->assertEquals($checkoutOptions, array(Payment::CHECKOUT_CUSTOM_CARD, Payment::CHECKOUT_CUSTOM_PIX, Payment::CHECKOUT_CUSTOM_TICKET));
    }

    public function testGetAvailableCheckoutsOptions_failure_returnEmpty(): void {
        $this->coreHelperMock
        ->expects($this->any())
        ->method('getAccessToken')
        ->willReturn('access_token');

        $this->coreHelperMock
        ->expects($this->any())
        ->method('getMercadoPagoPaymentMethods')
        ->willReturn(PaymentResponseMock::RESPONSE_PAYMENT_METHODS_FAILURE);

        $checkoutOptions = $this->payment->getAvailableCheckoutOptions('access_token');

        $this->assertEquals($checkoutOptions, array());
    }

    public function testGetAvailableCheckoutsOptions_exception_returnEmpty(): void {
        $this->coreHelperMock
        ->expects($this->any())
        ->method('getAccessToken')
        ->willReturn('access_token');

        $this->coreHelperMock
        ->expects($this->any())
        ->method('getMercadoPagoPaymentMethods')
        ->will($this->throwException(new \Exception()));

        $checkoutOptions = $this->payment->getAvailableCheckoutOptions('access_token');

        $this->assertEquals($checkoutOptions, array());
    }
}