<?php
/*
* Copyright (c) 2016 www.magebuzz.com
*/

class Magebuzz_Privatesale_Model_Category extends Mage_Core_Model_Abstract {
  public function _construct() {
    parent::_construct();
    $this->_init('privatesale/category');
  }
}