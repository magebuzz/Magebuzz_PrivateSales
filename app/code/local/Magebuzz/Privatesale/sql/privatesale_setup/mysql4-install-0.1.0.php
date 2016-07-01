<?php
/*
* Copyright (c) 2016 www.magebuzz.com
*/

$installer = $this;
$installer->startSetup();
$installer->run("
-- DROP TABLE IF EXISTS {$this->getTable('privatesale_product')};
CREATE TABLE {$this->getTable('privatesale_product')} (
  `privatesale_id` int(11) unsigned NOT NULL auto_increment,
  `product_id` int(10) unsigned NOT NULL,
  `group` int(10) NOT NULL,
  PRIMARY KEY (`privatesale_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- DROP TABLE IF EXISTS {$this->getTable('privatesale_category')};
CREATE TABLE {$this->getTable('privatesale_category')} (
  `privatesale_id` int(11) unsigned NOT NULL auto_increment,
  `category_id` int(10) unsigned NOT NULL,
  `group` int(10) NOT NULL,
  PRIMARY KEY (`privatesale_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- DROP TABLE IF EXISTS {$this->getTable('privatesale_event')};
CREATE TABLE {$this->getTable('privatesale_event')} (
  `event_id` int(11) unsigned NOT NULL auto_increment,
  `type` int(10) unsigned NOT NULL,
  `category_id` int(10) unsigned NOT NULL,
  `category` text NOT NULL,
  `label` text NOT NULL,
  `title` text NOT NULL,
  `start_date` DATETIME NOT NULL,
  `end_date` DATETIME NOT NULL,
  `status` int(10) unsigned NOT NULL,
  `position` TEXT NOT NULL,
  `image` TEXT NOT NULL,
  `note` TEXT NOT NULL,
  PRIMARY KEY (`event_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- DROP TABLE IF EXISTS {$this->getTable('privatesale_invite')};
CREATE TABLE {$this->getTable('privatesale_invite')} (
  `invite_id` int(11) unsigned NOT NULL auto_increment,
  `referer_id` TEXT NOT NULL,
  `referer_email` TEXT NOT NULL,
  `invited_customer_id` TEXT NOT NULL,
  `invited_customer_email` TEXT NOT NULL,
  `invited_customer_sign_up_code` TEXT NOT NULL,
  PRIMARY KEY (`invite_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
    ");
$installer->endSetup(); 
