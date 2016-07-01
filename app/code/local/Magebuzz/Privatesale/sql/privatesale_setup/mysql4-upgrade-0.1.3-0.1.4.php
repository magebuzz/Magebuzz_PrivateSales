<?php
/*
* Copyright (c) 2016 www.magebuzz.com
*/

$installer = $this;
$installer->startSetup();
$installer->run("
ALTER TABLE {$this->getTable('privatesale_event')} ADD `conditions_serialized` text NULL;
ALTER TABLE {$this->getTable('privatesale_event')} ADD `discount_amount` varchar(50);
ALTER TABLE {$this->getTable('privatesale_event')} ADD `url_key` text NULL;
");

$installer->endSetup(); 