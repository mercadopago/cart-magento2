<?php

namespace MercadoPago\Core\Model\System\Config\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class TypeBasicCheckout
 * @package MercadoPago\Core\Model\System\Config\Source
 */
class TypeBasicCheckout implements ArrayInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        $arr = [["value" => "redirect", 'label' => __("Redirect")]];
        return $arr;
    }
}
