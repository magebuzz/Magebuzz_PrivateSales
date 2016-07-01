<?php
/*
* @copyright Copyright (c) 2016 www.magebuzz.com
*/

class Magebuzz_Privatesale_Model_User extends Mage_Core_Model_Abstract {
  const STATUS_CUSTOMER_PENDING = 1;
  const STATUS_CUSTOMER_APPROVED = 2;
  const STATUS_CUSTOMER_REJECT = 3;

  public function _construct() {
    parent::_construct();
    $this->_init('privatesale/user');
  }

  public function loadByUserId($id) {
    if (!$id) {
      return FALSE;
    }
    $user = $this->load($id, 'user_id');
    if (!$user->getId()) {
      // user is not existed, create a new user
      $data = array('user_id' => $id, 'user_config' => '',);
      $user->setData($data)->save();
    }
    return $user;
  }

  static public function getOptionArray() {
    return array(self::STATUS_CUSTOMER_PENDING => Mage::helper('privatesale')->__('Pending'), self::STATUS_CUSTOMER_APPROVED => Mage::helper('privatesale')->__('Approved'), self::STATUS_CUSTOMER_REJECT => Mage::helper('privatesale')->__('Reject'));
  }
}