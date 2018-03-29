<?php
namespace MercadoPago\MercadoEnvios\Helper;

/**
 * Class CarrierData
 *
 * @package MercadoPago\MercadoEnvios\Helper
 */
class CarrierData
    extends Data
{
    /**
     *
     */
    const XML_PATH_ATTRIBUTES_MAPPING = 'carriers/mercadoenvios/attributesmapping';
    /**
     *
     */
    const ME_LENGTH_UNIT = 'cm';
    /**
     *
     */
    const ME_WEIGHT_UNIT = 'gr';

    /**
     * @var array
     */
    protected $_products = [];
    /**
     * @var
     */
    protected $_mapping;
    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $_productFactory;
    /**
     * @var \MercadoPago\Core\Logger\Logger
     */
    protected $_mpLogger;
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * @var array
     */
    protected $_maxWeight = ['mla' => '25000', 'mlb' => '30000', 'mlm' => ''];
    /**
     * @var array
     */
    protected $_individualDimensions = ['height' => ['mla' => ['min' => '0', 'max' => '70'], 'mlb' => ['min' => '2', 'max' => '105'], 'mlm' => ['min' => '0', 'max' => '80']],
                                        'width'  => ['mla' => ['min' => '0', 'max' => '70'], 'mlb' => ['min' => '11', 'max' => '105'], 'mlm' => ['min' => '0', 'max' => '80']],
                                        'length' => ['mla' => ['min' => '0', 'max' => '70'], 'mlb' => ['min' => '16', 'max' => '105'], 'mlm' => ['min' => '0', 'max' => '120']],
                                        'weight' => ['mla' => ['min' => '0', 'max' => '25000'], 'mlb' => ['min' => '0', 'max' => '30000'], 'mlm' => ['min' => '0', 'max' => '70000']],
    ];
    /**
     * @var array
     */
    protected $_globalMaxDimensions = ['mla' => '210',
                                       'mlb' => '200',
                                       'mlm' => '347',
    ];

    /**
     * @param $items
     *
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getDimensions($items)
    {
        $width = 0;
        $height = 0;
        $length = 0;
        $weight = 0;
        $bulk = 0;
        foreach ($items as $item) {
            $tempWidth = $this->_getShippingDimension($item, 'width');
            $tempHeight = $this->_getShippingDimension($item, 'height');
            $tempLength = $this->_getShippingDimension($item, 'length');
            $tempWeight = $this->_getShippingDimension($item, 'weight');
            $qty = $this->_helperItem->itemGetQty($item);
            $bulk += ($tempWidth * $tempHeight * $tempLength) * $qty;
            $width += $tempWidth * $qty;
            $height += $tempHeight * $qty;
            $length += $tempLength * $qty;
            $weight += $tempWeight * $qty;
        }
        $height = ceil($height);
        $width = ceil($width);
        $length = ceil($length);
        $weight = ceil($weight);

        $this->validateCartDimension($height, $width, $length, $weight);
        $bulk = ceil(pow($bulk, 1 / 3));

        return $bulk . 'x' . $bulk . 'x' . $bulk . ',' . $weight;

    }

    /**
     * @param $item
     * @param $type
     *
     * @return int|string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function _getShippingDimension($item, $type)
    {
        $attributeMapped = $this->_getConfigAttributeMapped($type);
        if (!empty($attributeMapped)) {
            if (!isset($this->_products[$item->getProductId()])) {
                $this->_products[$item->getProductId()] = $this->_productFactory->create()->load($item->getProductId());
            }
            $product = $this->_products[$item->getProductId()];
            $result = $product->getData($attributeMapped);
            $result = $this->getAttributesMappingUnitConversion($type, $result);
            $this->validateProductDimension($result, $type, $item);

            return $result;
        }

        return 0;
    }

    /**
     * @param $dimension
     * @param $type
     * @param $item
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function validateProductDimension($dimension, $type, $item)
    {
        $country = $this->scopeConfig->getValue('payment/mercadopago/country');
        if (empty((int)$dimension) || $dimension > $this->_individualDimensions[$type][$country]['max'] || $dimension < $this->_individualDimensions[$type][$country]['min']) {
            $this->log('Invalid dimension product: PRODUCT ', $item->getData());
            throw new \Magento\Framework\Exception\LocalizedException(new \Magento\Framework\Phrase('Invalid dimensions product'));
        }
    }

    /**
     * @param $height
     * @param $width
     * @param $length
     * @param $weight
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function validateCartDimension($height, $width, $length, $weight)
    {
        $country = $this->scopeConfig->getValue('payment/mercadopago/country');
        if (!isset($this->_globalMaxDimensions[$country])) {
            return;
        }
        if (($height + $width + $length) > $this->_globalMaxDimensions[$country]) {
            $this->log('Invalid dimensions in cart:', ['width' => $width, 'height' => $height, 'length' => $length, 'weight' => $weight,]);
            $this->_registry->register('mercadoenvios_msg', __('Package exceed maximum dimensions'));
            throw new \Magento\Framework\Exception\LocalizedException(new \Magento\Framework\Phrase('Invalid dimensions cart'));
        }
    }

    /**
     * @param $type
     *
     * @return null
     */
    protected function _getConfigAttributeMapped($type)
    {
        return (isset($this->getAttributeMapping()[$type]['code'])) ? $this->getAttributeMapping()[$type]['code'] : null;
    }

    /**
     * @return array
     */
    public function getAttributeMapping()
    {
        if (empty($this->_mapping)) {
            $mapping = $this->scopeConfig->getValue(self::XML_PATH_ATTRIBUTES_MAPPING);
            $mapping = unserialize($mapping);
            $mappingResult = [];
            foreach ($mapping as $key => $map) {
                $mappingResult[$key] = ['code' => $map['attribute_code'], 'unit' => $map['unit']];
            }
            $this->_mapping = $mappingResult;
        }

        return $this->_mapping;
    }

    /**
     * @param $attributeType
     * @param $value
     *
     * @return int|string
     */
    public function getAttributesMappingUnitConversion($attributeType, $value)
    {
        $this->_getConfigAttributeMapped($attributeType);

        if ($attributeType == 'weight') {
            //check if needs conversion
            if ($this->_mapping[$attributeType]['unit'] != self::ME_WEIGHT_UNIT) {
                $unit = new \Zend_Measure_Weight((float)$value);
                $unit->convertTo(\Zend_Measure_Weight::GRAM);

                return $unit->getValue();
            }

        } elseif ($this->_mapping[$attributeType]['unit'] != self::ME_LENGTH_UNIT) {
            $unit = new \Zend_Measure_Length((float)$value);
            $unit->convertTo(\Zend_Measure_Length::CENTIMETER);

            return $unit->getValue();
        }

        return $value;
    }

}