<?php
/*
* Copyright (c) 2016 www.magebuzz.com
*/

class Magebuzz_Privatesale_Model_Groups {
  public function toOptionArray() {
    $collection = Mage::getModel('customer/group')->getCollection()->getData();
    $groups = array();
    foreach ($collection as $item) {
			if ($item['customer_group_id'] == 0) {
				continue;
			}
      $temp = array('value' => $item['customer_group_id'], 'label' => $item['customer_group_code']);
      array_push($groups, $temp);
    }

    return $groups;
  }

  public function toArray() {
    $collection = Mage::getModel('customer/group')->getCollection()->getData();
    $groups = array();
    foreach ($collection as $item) {
			if ($item['customer_group_id'] == 0) {
				continue;
			}
      $groups[$item['customer_group_id']] = $item['customer_group_code'];
    }
    return $groups;
  }
}