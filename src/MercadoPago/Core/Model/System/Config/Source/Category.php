<?php
namespace MercadoPago\Core\Model\System\Config\Source;

/**
 * Class Category
 *
 * @package MercadoPago\Core\Model\System\Config\Source
 */
class Category
    implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @var \MercadoPago\Core\Helper\Data
     */
    protected $coreHelper;
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \MercadoPago\Core\Helper\Data                      $coreHelper
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \MercadoPago\Core\Helper\Data $coreHelper
    )
    {
        $this->scopeConfig = $scopeConfig;
        $this->coreHelper = $coreHelper;
    }

    /**
     * Return key sorted shop item categories
     * @return array
     */
    public function toOptionArray()
    {
        $this->coreHelper->log("Get Categories... ", 'mercadopago');

        try {
            $response = \MercadoPago\Core\Lib\RestClient::get("/item_categories");
        } catch (\Exception $e) {
            return [];
        }
        $this->coreHelper->log("API item_categories", 'mercadopago', $response);

        $response = $response['response'];

        $cat = array();
        $count = 0;
        foreach ($response as $v) {
            //force category others first
            if ($v['id'] == "others") {
                $cat[0] = ['value' => $v['id'], 'label' => __($v['description'])];
            } else {
                $count++;
                $cat[$count] = ['value' => $v['id'], 'label' => __($v['description'])];
            }

        };

        //force order by key
        ksort($cat);

        return $cat;
    }

}
