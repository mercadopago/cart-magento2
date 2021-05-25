<?php

namespace MercadoPago\Core\Model\System\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use MercadoPago\Core\Helper\Pix;

/**
 * Class PixExpirationMinutes
 */
class PixExpirationMinutes implements OptionSourceInterface
{


    /**
     * @return \string[][]
     */
    public function toOptionArray()
    {
        $values = [];

        foreach (Pix::EXPIRATION_TIME as $label => $value) {
            $values[] = [
                'label' => __($label),
                'value' => $value,
            ];
        }

        return $values;

    }//end toOptionArray()


}//end class
