<?php

/*
* @copyright   Copyright (c) 2014 www.magebuzz.com
*/

class Magebuzz_Privatesale_Block_Adminhtml_Customer_Grid_Renderer_Status extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract {
  public function render(Varien_Object $row) {

    $privateUser = Mage::getModel('privatesale/user')->loadByUserId($row->getId());
    $status = $privateUser->getUserStatus();
    if ($status == null || $status == 0 || $status == 2) {
      $name = "<p>Approved</p>";
    } elseif ($status == 1) {
      $name = "<p>Pending</p>";
    } else {
      $name = "<p>Reject</p>";
    }

    return $name;
  }
}