<?php

namespace MercadoPago\MercadoEnvios\Helper;

/**
 * Class ItemData
 *
 * @package MercadoPago\MercadoEnvios\Helper
 */
class ItemData
    extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @param $item
     *
     * @return bool
     */
    public function itemHasChildren($item)
    {
        $children = $item->getChildren();

        return (!empty($children) || in_array('getHasChildren',get_class_methods($item)) && $item->getHasChildren());
    }

    /**
     * @param $item
     *
     * @return mixed
     */
    public function itemGetQty($item)
    {
        if ($item->getParentItem()) {
            $item = $item->getParentItem();
        }
        $qty = (in_array('getQty',get_class_methods($item))) ? $item->getQty() : $item->getQtyOrdered();

        return $qty;
    }
}