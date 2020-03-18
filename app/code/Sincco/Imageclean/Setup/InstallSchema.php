<?php
/**
 * # NOTICE OF LICENSE
 * This work is licensed under a ***Creative Commons Attribution-NonCommercial-
 * NoDerivs 3.0 Unported License*** http://creativecommons.org/licenses/by-nc-nd/3.0
 *
 * ## Authors
 *
 * Iván Miranda @ivanmiranda
 */
/**
 * # NOTICE OF LICENSE
 * This work is licensed under a ***Creative Commons Attribution-NonCommercial-
 * NoDerivs 3.0 Unported License*** http://creativecommons.org/licenses/by-nc-nd/3.0
 *
 * ## Authors
 *
 * Iván Miranda @ivanmiranda
 */
namespace Sincco\Imageclean\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class InstallSchema implements InstallSchemaInterface
{
	public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
	{
		$installer = $setup;
		$installer->startSetup();

		$table = $installer->getConnection()
			->newTable($installer->getTable('imageclean'))
			->addColumn(
				'imageclean_id',
				\Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
				null,
				['identity' => true,'nullable' => false,'primary' => true,'unsigned' => true],
				'ID'
			)
			->addColumn(
				'filename',
				\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
				255,
				['nullable' => false],
				'Filename'
			)
			->addIndex(
				$installer->getIdxName(
					'imageclean',
					['filename'],
					\Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE
				),
				['filename'],
				['type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE]
			);
		$installer->getConnection()->createTable($table);
	}
}