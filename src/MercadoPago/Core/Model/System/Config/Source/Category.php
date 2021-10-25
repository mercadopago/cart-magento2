<?php

namespace MercadoPago\Core\Model\System\Config\Source;

/**
 * Class Category
 *
 * @package MercadoPago\Core\Model\System\Config\Source
 */
class Category implements \Magento\Framework\Option\ArrayInterface
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
     * @param \MercadoPago\Core\Helper\Data $coreHelper
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \MercadoPago\Core\Helper\Data $coreHelper
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->coreHelper = $coreHelper;
    }

    /**
     * Return key sorted shop item categories
     * @return array
     */
    public function toOptionArray()
    {
        try {
            $access_token = $this->coreHelper->getAccessToken();
            $response = \MercadoPago\Core\Lib\RestClient::get("/item_categories", null, ["Authorization: Bearer " . $access_token]);
        } catch (\Exception $e) {
            $this->coreHelper->log("Category:: An error occurred at the time of obtaining the categories: " . $e);
            return [];
        }

        $response = $response['response'];

        $cat = [];
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

        $this->coreHelper->log("Category:: Displayed", 'mercadopago', $cat);
        return $cat;
    }
}
