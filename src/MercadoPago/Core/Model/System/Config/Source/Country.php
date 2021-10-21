<?php

namespace MercadoPago\Core\Model\System\Config\Source;

/**
 * Class Country
 *
 * @package MercadoPago\Core\Model\System\Config\Source
 */
class Country implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Return available country array
     * @return array
     */
    public function toOptionArray()
    {
        $country = [];
        $country[] = ['value' => "mla", 'label' => __("Argentina"), 'code' => 'AR'];
        $country[] = ['value' => "mlb", 'label' => __("Brasil"), 'code' => 'BR'];
        $country[] = ['value' => "mco", 'label' => __("Colombia"), 'code' => 'CO'];
        $country[] = ['value' => "mlm", 'label' => __("Mexico"), 'code' => 'MX'];
        $country[] = ['value' => "mlc", 'label' => __("Chile"), 'code' => 'CL'];
        $country[] = ['value' => "mlv", 'label' => __("Venezuela"), 'code' => 'VE'];
        $country[] = ['value' => "mpe", 'label' => __("Perú"), 'code' => 'PE'];
        $country[] = ['value' => "mlu", 'label' => __("Uruguay"), 'code' => 'UY'];

        //force order by key
        ksort($country);

        return $country;
    }
}
