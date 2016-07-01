<?php
/*
* @copyright Copyright (c) 2016 www.magebuzz.com
*/

class Magebuzz_Privatesale_Model_Statuswaiting extends Varien_Object {
  const STATUS_PENDING = 1;
  const STATUS_APPROVED = 2;
  const STATUS_REJECT = 3;

  static public function getOptionArray() {
    return array(self::STATUS_PENDING => Mage::helper('privatesale')->__('Pending'), self::STATUS_APPROVED => Mage::helper('privatesale')->__('Approved'), self::STATUS_REJECT => Mage::helper('privatesale')->__('Reject'));
  }
}