<?php
namespace MercadoPago\Core\Block;

/**
 * Class Info
 *
 * @package MercadoPago\Core\Block
 */
class Info extends \Magento\Payment\Block\Info
{
    /**
     * Constructor
     *
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Prepare information specific to current payment method
     *
     * @param null | array $transport
     * @return \Magento\Framework\DataObject
     */
    protected function _prepareSpecificInformation($transport = null)
    {
        $transport = parent::_prepareSpecificInformation($transport);
        $data = [];
        $info = $this->getInfo();
        $fields = [
            ["field" => "cardholderName", "title" => __("Card Holder Name")],
            ["field" => "trunc_card", "title" => __("Card Number")],
            ["field" => "payment_method", "title" => __("Payment Method")],
            ["field" => "expiration_date", "title" => __("Expiration Date")],
            ["field" => "installments", "title" => __("Installments")],
            ["field" => "statement_descriptor", "title" => __("Statement Descriptor")],
            ["field" => "payment_id", "title" => __("Payment id (MercadoPago)")],
            ["field" => "status", "title" => __("Payment Status")],
            ["field" => "status_detail", "title" => __("Payment Detail")],
            ["field" => "activation_uri", "title" => __("Generate Ticket")],
            ["field" => "payment_id_detail", "title" => __("Mercado Pago Payment Id")],
        ];

        foreach ($fields as $field) {

            if ($info->getAdditionalInformation($field['field']) != "") {
                $text = $field['title'];
                $data[$text->getText()] = $info->getAdditionalInformation($field['field']);
            };
        };

        if ($info->getAdditionalInformation('payer_identification_type') != "") {
            $text = __($info->getAdditionalInformation('payer_identification_type'), $info->getAdditionalInformation('payer_identification_number'));
            $data[$text->getText()] = $info->getAdditionalInformation('payer_identification_number');
        }

        return $transport->setData(array_merge($data, $transport->getData()));
    }

}
