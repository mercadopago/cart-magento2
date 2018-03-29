<?php
namespace MercadoPago\MercadoEnvios\Model\Adminhtml\Source\Label;

/**
 * Class Mode
 *
 * @package MercadoPago\MercadoEnvios\Model\Adminhtml\Source\Label
 */
class Mode
    implements \Magento\Framework\Option\ArrayInterface
{

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [['value' => 'pdf' , 'label' => 'PDF'],['value' => 'zpl2' , 'label' => 'ZIP']];
    }

}
