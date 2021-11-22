<?php

namespace MercadoPago\Test\Unit\Model;

use MercadoPago\Core\Helper\ConfigData;
use MercadoPago\Test\Unit\Constants\Config;
use MercadoPago\Test\Unit\Constants\Response;
use MercadoPago\Test\Unit\Constants\PaymentMethods;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class CoreTest extends TestCase
{
    /**
     * @var Core
     */
    private $storeManager;

    /**
     * @var MockObject
     */
    private $coreHelper;

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
}