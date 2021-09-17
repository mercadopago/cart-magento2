<?php

namespace MercadoPago\Core\Model\System\Config\Source;

/**
 * Class TypeCheckout
 *
 * @package MercadoPago\Core\Model\System\Config\Source
 */
class TypeCheckout implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Return available checkout types
     * @return array
     */
    public function toOptionArray()
    {
        $arr = [
            ["value" => "iframe", 'label' => __("Iframe")],
            ["value" => "redirect", 'label' => __("Redirect")],
            ["value" => "lightbox", 'label' => __("Lightbox")]
        ];

        return $arr;
    }
}
