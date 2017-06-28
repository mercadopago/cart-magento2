<?php
namespace MercadoPago\MercadoEnvios\Model\System\Config\Source;

/**
 * Class Method
 *
 * @package MercadoPago\MercadoEnvios\Model\System\Config\Source
 */
class Method
    implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var array
     */
    protected $_countryOptions = [
        'mla' => [
            ['value' => 73328, 'label' => 'Normal'],
            ['value' => 73330, 'label' => 'Prioritario']
        ],
        'mlb' => [
            ['value' => 100009, 'label' => 'Normal'],
            ['value' => 182, 'label' => 'Expresso'],
        ],
        'mlm' => [
            ['value' => 501245, 'label' => 'DHL Estándar'],
            ['value' => 501345, 'label' => 'DHL Express'],
        ]
    ];

    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    )
    {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Return key sorted shop item categories
     * @return array
     */
    public function toOptionArray()
    {
        $country = $this->scopeConfig->getValue('payment/mercadopago/country');
        if (isset($this->_countryOptions[$country])) {
            return $this->_countryOptions[$country];
        }
        return [];
    }

    /**
     * @return array
     */
    public function getAvailableCodes() {
        $methods = $this->toOptionArray();
        $codes = [];
        foreach ($methods as $method) {
            $codes[] = $method['value'];
        }
        return $codes;
    }

}
