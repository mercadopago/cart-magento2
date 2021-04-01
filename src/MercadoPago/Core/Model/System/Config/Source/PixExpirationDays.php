<?php

namespace MercadoPago\Core\Model\System\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class PixExpirationDays
 * @package MercadoPago\Core\Model\System\Config\Source
 */
class PixExpirationDays implements OptionSourceInterface
{

    /**
     * @return \string[][]
     */
    public function toOptionArray()
    {
        return [
            [
              'label' => __('1 day'),
              'value' => '1',
            ],
            [
              'label' => __('2 days'),
              'value' => '2',
            ],
            [
              'label' => __('3 days'),
              'value' => '3',
            ],
            [
              'label' => __('4 days'),
              'value' => '4',
            ],
            [
              'label' => __('5 days'),
              'value' => '5',
            ],
            [
              'label' => __('6 days'),
              'value' => '6',
            ],
            [
              'label' => __('7 days'),
              'value' => '7',
            ],
        ];
    }
}
