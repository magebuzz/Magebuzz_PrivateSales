<?php
/*
* @copyright Copyright (c) 2016 www.magebuzz.com
*/
class Magebuzz_Privatesale_Model_Restrictedoptions extends Varien_Object {
  const HIDE_PRICE = 1;
  const HIDE_ADD_TO_CART = 2;
  const HIDE_ITEM = 3;

  static public function toOptionArray() {
    return array(self::HIDE_ITEM => Mage::helper('privatesale')->__('Hide Items'), self::HIDE_PRICE => Mage::helper('privatesale')->__('Hide Price'), self::HIDE_ADD_TO_CART => Mage::helper('privatesale')->__('Hide Button Add To Cart'),);
  }
}