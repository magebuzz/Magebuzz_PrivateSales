<?php
/*
* Copyright (c) 2016 www.magebuzz.com
*/

class Magebuzz_Privatesale_Model_Mysql4_Category extends Mage_Core_Model_Mysql4_Abstract {
  public function _construct() {
    $this->_init('privatesale/category', 'privatesale_id');
  }
}