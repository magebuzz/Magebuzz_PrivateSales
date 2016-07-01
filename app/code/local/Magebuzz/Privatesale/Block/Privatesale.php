<?php
/*
* @copyright Copyright (c) 2016 www.magebuzz.com
*/

class Magebuzz_Privatesale_Block_Privatesale extends Mage_Core_Block_Template {

  public function isShowBanner() {
    return (int)Mage::getStoreConfig('privatesalesession/privatesale_options/show_banner');
  }

  public function getAllAvailableEvents() {
    $customerGroup = Mage::getSingleton('customer/session')->getCustomerGroupId();
    $allList = Mage::getModel('privatesale/event')
      ->getCollection()
      ->setOrder('start_date', 'ASC')
      ->getData();
    $avaiList = array();
    foreach ($allList as $item) {
      $privateCatagoryCollection = Mage::getModel('privatesale/category')->getCollection();
      $privateCatagoryCollection->addFieldToFilter('category_id', $item['category_id']);
      $data = $privateCatagoryCollection->getData();
      if (empty($data)) {
        if (in_array($customerGroup, unserialize($item['customer_group_ids']))) {
          array_push($avaiList, $item);
        }
      } else {
        foreach ($data as $value) {
          if ($value['group'] == $customerGroup) {
            array_push($avaiList, $item);
            break;
          }
        }
      }
    }
    return $avaiList;
  }

  public function getCurrentCategoryId() {
    return Mage::registry('current_category')->getId();
  }

  // All available upcomming event for CURRENT user
  public function getAllUpcommingEvents() {
    $avaiList = $this->getAllAvailableEvents();
    $now = Mage::app()->getLocale()->date(Mage::getModel('core/date')->date(), Varien_Date::DATETIME_INTERNAL_FORMAT, null, FALSE);
    $upcommingEvents = array();
    foreach ($avaiList as $item) {
      $startTime = Mage::app()->getLocale()->date($item['start_date'], Varien_Date::DATETIME_INTERNAL_FORMAT, null, FALSE);
      if ($startTime->compare($now) > 0) array_push($upcommingEvents, $item);
    }
    return $upcommingEvents;
  }

  // All available happenning event for CURRENT user
  public function getAllHappenningEvents() {
    $avaiList = $this->getAllAvailableEvents();
    $now = Mage::app()->getLocale()->date(Mage::getModel('core/date')->date(), Varien_Date::DATETIME_INTERNAL_FORMAT, null, FALSE);
    $happenningEvents = array();
    foreach ($avaiList as $item) {
      $startTime = Mage::app()->getLocale()->date($item['start_date'], Varien_Date::DATETIME_INTERNAL_FORMAT, null, FALSE);
      $endTime = Mage::app()->getLocale()->date($item['end_date'], Varien_Date::DATETIME_INTERNAL_FORMAT, null, FALSE);
      if ($startTime->compare($now) <= 0 && $endTime->compare($now) >= 0) array_push($happenningEvents, $item);
    }
    return $happenningEvents;
  }

  // upcomming and happenning
  public function getAvailableEvents() {
    $avaiList = $this->getAllAvailableEvents();

    $now = Mage::app()->getLocale()->date(Mage::getModel('core/date')->date(), Varien_Date::DATETIME_INTERNAL_FORMAT, null, FALSE);
    $avaiEvents = array();
    foreach ($avaiList as $item) {
      $endTime = Mage::app()->getLocale()->date($item['end_date'], Varien_Date::DATETIME_INTERNAL_FORMAT, null, FALSE);
      if ($endTime->compare($now) >= 0) array_push($avaiEvents, $item);
    }
    return $avaiEvents;
  }

  // get Events display in home page

  public function getAllEventsInHomePage() {
    $avaiList = $this->getAllAvailableEvents();
    $eventIds = $this->getEventIds();
    $now = Mage::app()->getLocale()->date(Mage::getModel('core/date')->date(), Varien_Date::DATETIME_INTERNAL_FORMAT, null, FALSE);
    $eventIdsArray = explode(',', $eventIds);
    $eventsArray = array();
    foreach ($avaiList as $item) {
      $endTime = Mage::app()->getLocale()->date($item['end_date'], Varien_Date::DATETIME_INTERNAL_FORMAT, null, FALSE);
      if (in_array($item['event_id'], $eventIdsArray)) {
        if ($endTime->compare($now) >= 0) {
          array_push($eventsArray, $item);
        }

      }
    }
    return $eventsArray;
  }

  //  public function isAllowedToSeeBanner() {
  //    $avaiList = $this->getAvailableEvents();
  //    foreach($avaiList as $item) {
  //      if($this->getCurrentCategoryId()==$item['category_id']) return true;
  //    }
  //    return false;
  //  }

  //public function getCurrentShowEvent() {
  //    $avaiList = $this->getAvailableEvents();
  //    foreach($avaiList as $item) {
  //      if($this->getCurrentCategoryId()==$item['category_id']) {
  //        return $item;
  //      }
  //    }
  //    return false;
  //  }

  public function isHappened($item) {
    $now = Mage::app()->getLocale()->date(Mage::getModel('core/date')->date(), Varien_Date::DATETIME_INTERNAL_FORMAT, null, FALSE);
    $startTime = Mage::app()->getLocale()->date($item['start_date'], null, null, FALSE);
    if ($startTime->compare($now) >= 0) return FALSE; else return TRUE;
  }

  public function countDownText($item) {
    $str = null;
    if ($this->isHappened($item)) $str = Mage::helper('privatesale')->__('Event will end in:'); else $str = Mage::helper('privatesale')->__('Event will start in: ');
    return $str;
  }

  public function getCurrentTime() {
    $now = Mage::app()->getLocale()->date(Mage::getModel('core/date')->date(), Varien_Date::DATETIME_INTERNAL_FORMAT, null, FALSE);
    return $now;
  }

}