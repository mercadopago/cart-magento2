<?php

namespace MercadoPago\Core\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * @codeCoverageIgnore
 */
class UpgradeSchema
    implements UpgradeSchemaInterface
{
    /**
     * {@inheritdoc}
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();
        $connection = $installer->getConnection();

        $invoiceTable = $installer->getTable('sales_invoice');
        $creditMemoTable = $installer->getTable('sales_creditmemo');
        $salesTable = $installer->getTable('sales_order');
        $quoteTable = $installer->getTable('quote');


        /*********** VERSION 1.0.2 ADD FINANCE COST COLUMNS TO INVOICE AND CREDITMEMO ***********/
        if (version_compare($context->getVersion(), '1.0.2', '<=')) {

            $columns = [
                'finance_cost_amount'      => [
                    'type'     => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                    'length'   => '12,4',
                    'nullable' => true,
                    'comment'  => 'Finance Cost Amount',
                ],
                'base_finance_cost_amount' => [
                    'type'     => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                    'length'   => '12,4',
                    'nullable' => true,
                    'comment'  => 'Base Finance Cost Amount',
                ]
            ];

            foreach ($columns as $name => $definition) {
                $connection->addColumn($invoiceTable, $name, $definition);
                $connection->addColumn($creditMemoTable, $name, $definition);
            }
        }

        /*********** VERSION 1.0.3 ADD DISCOUNT COUPON COLUMNS TO ORDER, QUOTE, INVOICE, CREDITMEMO ***********/
        if (version_compare($context->getVersion(), '1.0.3', '<=')) {
            $columns = [
                'discount_coupon_amount'      => [
                    'type'     => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                    'length'   => '12,4',
                    'nullable' => true,
                    'comment'  => 'Discount coupon Amount',
                ],
                'base_discount_coupon_amount' => [
                    'type'     => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                    'length'   => '12,4',
                    'nullable' => true,
                    'comment'  => 'Base Discount coupon Amount',
                ]
            ];

            foreach ($columns as $name => $definition) {
                $connection->addColumn($invoiceTable, $name, $definition);
                $connection->addColumn($creditMemoTable, $name, $definition);
                $connection->addColumn($salesTable, $name, $definition);
                $connection->addColumn($quoteTable, $name, $definition);
            }

        }


        /*********** VERSION 1.0.4 ADD DISCOUNT COUPON COLUMNS TO QUOTE_ADDRESS ***********/

        if (version_compare($context->getVersion(), '1.0.4', '<=')) {
            $quoteAddressTable = $installer->getTable('quote_address');
            $columns = ['discount_coupon_amount'      => ['type'     => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                                                          'length'   => '12,4',
                                                          'nullable' => true,
                                                          'comment'  => 'Discount coupon Amount',],
                        'base_discount_coupon_amount' => ['type'     => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                                                          'length'   => '12,4',
                                                          'nullable' => true,
                                                          'comment'  => 'Base Discount coupon Amount',]];

            foreach ($columns as $name => $definition) {
                $connection->addColumn($quoteAddressTable, $name, $definition);
            }
        }

        $setup->endSetup();

        /*********** VERSION 1.0.5 ADD FINANCING COST COLUMN TO QUOTE_ADDRESS ***********/

        if (version_compare($context->getVersion(), '1.0.5', '<=')) {
            $quoteAddressTable = $installer->getTable('quote_address');
            $columns = ['finance_cost_amount'      => ['type'     => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                                                          'length'   => '12,4',
                                                          'nullable' => true,
                                                          'comment'  => 'Finance Cost Amount'],
                        'base_finance_cost_amount' => ['type'     => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                                                          'length'   => '12,4',
                                                          'nullable' => true,
                                                          'comment'  => 'Base Finance Cost Amount']];

            foreach ($columns as $name => $definition) {
                $connection->addColumn($quoteAddressTable, $name, $definition);
            }
        }

        $setup->endSetup();
    }
}
