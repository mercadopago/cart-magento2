<?php
namespace MercadoPago\MercadoEnvios\Model\System\Config\Source;

/**
 * Class FreeMethod
 *
 * @package MercadoPago\MercadoEnvios\Model\System\Config\Source
 */
class FreeMethod
    extends \MercadoPago\MercadoEnvios\Model\System\Config\Source\Method
{
    /**
     *
     * @return array
     */
    public function toOptionArray()
    {
        $arr = parent::toOptionArray();
        array_unshift($arr, ['value' => '', 'label'=> __('None')]);
        return $arr;
    }

}
