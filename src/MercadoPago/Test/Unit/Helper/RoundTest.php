<?php

namespace MercadoPago\Test\Unit\Helper;

use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Model\OrderFactory;
use \MercadoPago\Core\Block\Info;
use MercadoPago\Core\Helper\Round;
use \PHPUnit\Framework\TestCase;

class HelperTest extends TestCase
{

    public function testRoundIntToUp()
    {
        $result = Round::roundInteger(1.9);
        $this->assertEquals(2, $result);

        $result = Round::roundInteger(1.5);
        $this->assertEquals(2, $result);
    }

    public function testRoundIntToDown()
    {
        $result = Round::roundInteger(1.4);
        $this->assertEquals(1, $result);

        $result = Round::roundInteger(1.1);
        $this->assertEquals(1, $result);
    }

    public function testRoundFloatToUp()
    {
        $result = Round::roundWithoutSiteId(99.4999);
        $this->assertEquals(99.5, $result);

        $result = Round::roundWithoutSiteId(55.999);
        $this->assertEquals(56, $result);
    }

    public function testRoundFloatToDown()
    {
        $result = Round::roundWithoutSiteId(66.404444);
        $this->assertEquals(66.40, $result);

        $result = Round::roundWithoutSiteId(75.890000);
        $this->assertEquals(75.89, $result);
    }

    public function testRoundWithSiteFloatToUp()
    {
        $result = Round::roundWithSiteId(142.87777, 'MLB');
        $this->assertEquals(142.88, $result);

        $result = Round::roundWithSiteId(545.999, 'MLA');
        $this->assertEquals(546, $result);
    }

    public function testRoundWithSiteFloatToDown()
    {
        $result = Round::roundWithSiteId(66.672333, 'MLM');
        $this->assertEquals(66.67, $result);

        $result = Round::roundWithSiteId(755.890000, 'MLU');
        $this->assertEquals(755.89, $result);
    }

    public function testRoundWithSiteIntToUp()
    {
        $result = Round::roundWithSiteId(142.87777, 'MLC');
        $this->assertEquals(143, $result);

        $result = Round::roundWithSiteId(545.999, 'MCO');
        $this->assertEquals(546, $result);
    }

    public function testRoundWithSiteIntToDown()
    {
        $result = Round::roundWithSiteId(66.472333, 'MCO');
        $this->assertEquals(66, $result);

        $result = Round::roundWithSiteId(755.190000, 'MLC');
        $this->assertEquals(755, $result);
    }

}//end class
