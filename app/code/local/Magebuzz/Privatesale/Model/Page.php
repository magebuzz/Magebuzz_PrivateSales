<?php
/*
* @copyright Copyright (c) 2016 www.magebuzz.com
*/

class Magebuzz_Privatesale_Model_Page {
  public function toOptionArray() {
    return array(array('value' => 0, 'label' => Mage::helper('privatesale')->__('No config')), array('value' => 1, 'label' => Mage::helper('privatesale')->__('Category')), array('value' => 2, 'label' => Mage::helper('privatesale')->__('Product')), array('value' => 3, 'label' => Mage::helper('privatesale')->__('Whole website')),);
  }

  public function toArray() {
    return array(0 => Mage::helper('privatesale')->__('No config'), 1 => Mage::helper('privatesale')->__('Category'), 2 => Mage::helper('privatesale')->__('Product'), 3 => Mage::helper('privatesale')->__('Whole website'),);
  }
}