<?php
/*
* Copyright (c) 2016 www.magebuzz.com
*/
$installer = $this;
$installer->startSetup();
$installer->run("
	ALTER TABLE {$this->getTable('privatesale_event')} ADD `customer_group_ids` TEXT;
");
$installer->endSetup();