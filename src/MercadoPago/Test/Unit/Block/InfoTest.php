<?php

namespace MercadoPago\Test\Unit\Block;

use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Model\OrderFactory;
use \MercadoPago\Core\Block\Info;
use \PHPUnit\Framework\TestCase;

class InfoTest extends TestCase
{
    private $object;

    protected function setUp()
    {
        $contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $orderFactorytMock = $this->getMockBuilder(OrderFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->object = new Info($contextMock, $orderFactorytMock);
    }

    public function testDummy()
    {
        $this->assertTrue(true);
    }

    public function testInfoInstance()
    {
        $this->assertInstanceOf(Info::class, $this->object);
    }
}
