<?php

namespace MercadoPago\Test\Unit\Block\Fieldset;

use MercadoPago\Core\Block\Adminhtml\System\Config\Fieldset\Payment;
use MercadoPago\Test\Unit\Constants\Response;
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
    protected $mpCache;

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
        $this->mpCache = $arguments['mpCache'];

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

        $this->mpCache->expects(self::any())
        ->method('getFromCache')
        ->willReturn(null);

        $this->scopeConfigMock->expects(self::any())
        ->method('getValue')
        ->willReturn('APP_USR-00000000000-000000-000000-0000000000');
        
        $this->coreHelperMock->expects(self::any())
        ->method('getMercadoPagoPaymentMethods')
        ->with('APP_USR-00000000000-000000-000000-0000000000')
        ->willReturn(Response::RESPONSE_PAYMENT_METHODS_SUCCESS_MLB);

        $this->mpCache->expects(self::any())->method('saveCache');

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
}