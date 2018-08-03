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
            ["field" => "cardholderName", "title" => "Card Holder Name"],
            ["field" => "trunc_card", "title" => "Card Number"],
            ["field" => "payment_method", "title" => "Payment Method"],
            ["field" => "expiration_date", "title" => "Expiration Date"],
            ["field" => "installments", "title" => "Installments"],
            ["field" => "statement_descriptor", "title" => "Statement Descriptor"],
            ["field" => "payment_id", "title" => "Payment id (MercadoPago)"],
            ["field" => "status", "title" => "Payment Status"],
            ["field" => "status_detail", "title" => "Payment Detail"],
            ["field" => "activation_uri", "title" => "Generate Ticket"],
            ["field" => "payment_id_detail", "title" => "Mercado Pago Payment Id"],
        ];

        foreach ($fields as $field) {

            if ($info->getAdditionalInformation($field['field']) != "") {
                $text = __($field['title'], $info->getAdditionalInformation($field['field']));
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
