<?php
/*
* Copyright (c) 2016 www.magebuzz.com
*/

$installer = $this;
$installer->startSetup();
$installer->run("
ALTER TABLE {$this->getTable('privatesale_invite')} ADD `is_admin` int(10) unsigned NOT NULL DEFAULT '0';

-- DROP TABLE IF EXISTS {$this->getTable('privatesale_waiting')};
CREATE TABLE {$this->getTable('privatesale_waiting')} (
`waiting_id` int(11) unsigned NOT NULL auto_increment,  
`name_waiting` varchar(50),      
`email_waiting` varchar(50) NOT NULL,  
`status` int(10) unsigned NOT NULL,    
PRIMARY KEY (`waiting_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8; 

DROP TABLE IF EXISTS {$this->getTable('privatesale_user')};
CREATE TABLE {$this->getTable('privatesale_user')} (
`id` int(11) unsigned NOT NULL auto_increment,
`user_id` int(11) unsigned NOT NULL,
`user_config` int(11) unsigned NOT NULL DEFAULT '0',
`user_status` int(11) unsigned NOT NULL DEFAULT '0',
`is_sendmail` int(11) unsigned NOT NULL DEFAULT '0',
UNIQUE(`user_id`),
FOREIGN KEY (`user_id`) REFERENCES {$this->getTable('customer/entity')} (`entity_id`) ON DELETE CASCADE ON UPDATE CASCADE,  
PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
");

$allStores = Mage::app()->getStores();
$allStores = array();
foreach ($allStores as $_eachStoreId => $val) {
  $allStores[] = Mage::app()->getStore($_eachStoreId)->getId();
}
$allStores[] = 0;

$dataInsertCMS = array('title' => 'Welcome', 'identifier' => 'landing', 'is_active' => '1', 'content' => "{{block type='privatesale/landing' name='privatesale-landing-page' template='privatesale/landing.phtml'}}", 'root_template' => 'empty', 'store_id' => $allStores,);
$cmsModel = Mage::getModel('cms/page');
$check = $cmsModel->checkIdentifier('landing', 0);
if (!$check) {
  $cmsModel->setData($dataInsertCMS)->save();
}

$installer->endSetup(); 