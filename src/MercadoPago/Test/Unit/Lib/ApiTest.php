<?php

namespace MercadoPago\Test\Unit\Lib;

use MercadoPago\Core\Lib\Api;
use PHPUnit\Framework\TestCase;

class ApiTest extends TestCase
{
    /**
     * @var Api
     */
    private $api;

    /**
     * @var string
     */
    private $default_access_token =  "some_access_token";

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->api = new Api($this->default_access_token);
    }

    /**
     * @dataProvider sandboxProvider
     */
    public function testSandboxMode($functionParam, $expected): void
    {
        $this->assertEquals($expected, $this->api->sandbox_mode($functionParam));
    }

    public function sandboxProvider(): array
    {
        return [
            [false, false],
            [null, false],
            [true, true],
        ];
    }

}
