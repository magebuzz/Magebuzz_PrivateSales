<?php
/*
* @copyright Copyright (c) 2016 www.magebuzz.com
*/

class Magebuzz_Privatesale_Helper_Data extends Mage_Core_Helper_Abstract {
  const XML_PATH_SELECTED_EMAIL_TEMPLATE = 'privatesalesession/email_options/invite_email_template';
  const XML_PATH_NOTIFICATION_EMAIL_TEMPLATE = 'privatesalesession/email_options/send_request_invitation_template';
  const XML_PATH_NOTIFICATION_SEND_TO_CUSTOMER_EMAIL_TEMPLATE = 'privatesalesession/email_options/send_email_when_have_new_event';
  const XML_PATH_SELECTED_EMAIL_SENDER_IDENTITY = 'privatesalesession/email_options/email_sender';
  const XML_RECIEVED_EMAIL = 'privatesalesession/email_options/recieved_email';
  const XML_PATH_SPLIT_EVENT_ENABLE = 'privatesalesession/privatesale_options/current_upcoming_events';

  public function getConfigSplitEvent() {
    return (int)Mage::getStoreConfig(self::XML_PATH_SPLIT_EVENT_ENABLE);
  }

  public function sendInviteEmail($to, $name, $emailvars) {
    try {
      $translate = Mage::getSingleton('core/translate');
      $translate->setTranslateInline(FALSE);
      $storeId = Mage::app()->getStore()->getId();
      $emailvars['invited_customer_email'] = $to;
      Mage::getModel('core/email_template')->setDesignConfig(array('area' => 'frontend', 'store' => $storeId))->sendTransactional(Mage::getStoreConfig(self::XML_PATH_SELECTED_EMAIL_TEMPLATE, $storeId), Mage::getStoreConfig(self::XML_PATH_SELECTED_EMAIL_SENDER_IDENTITY, $storeId), $to, $name, $emailvars);
    } catch (Exception $ex) {

    }
    $translate->setTranslateInline(TRUE);
  }

  public function restrictedOptionConfig() {
    return Mage::getStoreConfig('privatesalesession/privatesale_options/restricted_option');
  }

  public function loadCurrentImage($eventId) {
    $event = Mage::getModel('privatesale/event')->load($eventId);
    return $event->getImage();
  }

  public function sendMail($template, $sendTo, $emailvars) {
    try {
      $translate = Mage::getSingleton('core/translate');
      $translate->setTranslateInline(FALSE);
      $storeId = Mage::app()->getStore()->getId();
      Mage::getModel('core/email_template')->setDesignConfig(array('area' => 'frontend', 'store' => $storeId))->sendTransactional($template, Mage::getStoreConfig(self::XML_PATH_SELECTED_EMAIL_SENDER_IDENTITY, $storeId), $sendTo, null, $emailvars);
    } catch (Exception $e) {
    }
    $translate->setTranslateInline(TRUE);
  }

  public function getUserConfig() {
    if (Mage::getModel('privatesale/user')->loadByUserId(Mage::getSingleton('customer/session')->getCustomerId())->getUserConfig()) return TRUE; else return FALSE;
  }

  public function sendEmailNotificationToCustomer($event) {

    $data = $event->getData();
    if (!isset($data['image']) || $data['image'] == '') {
      $data['image'] = $this->eventImage();
    } else {
      $data['image'] = $this->eventImage($data['image']);
    }

    $arrayStatus = array(1 => 'Upcomming', 2 => 'Happening', 3 => 'Expired');
    $data['status'] = $arrayStatus[$data['status']];
    $data['event_url'] = $this->getEventUrl($data);
    $privateUser = Mage::getModel('privatesale/user')->getCollection()->addFieldToFilter('user_config', 1);
    //Zend_Debug::dump($privateUser->getData());die();
    $customer = Mage::getModel('customer/customer');
    $template = Mage::getStoreConfig(self::XML_PATH_NOTIFICATION_SEND_TO_CUSTOMER_EMAIL_TEMPLATE);
    if ($privateUser->getSize() > 0) {
      foreach ($privateUser as $user) {
        $customerInfo = $customer->load($user->getUserId());
        $email = $customerInfo->getEmail();
        $name = $customerInfo->getName();
        $data['customer_name'] = $name;
        $this->sendMail($template, $email, $data);
      }
    }
  }

  public function eventImage($event = "") {
    $url = "";
    if ($event == "") {
      $defaultImage = Mage::getStoreConfig('privatesalesession/privatesale_options/default_event_image');
      if ($defaultImage != "") {
        $url = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . "privatesales/default/" . $defaultImage;
      } else {
        $url = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . "privatesales/default/default/Image.png";
      }
    } else {
      $url = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . "privatesales/events/" . $event;
    }
    return $url;
  }

  public function generateUrlKey($title) {
    $url_key = preg_replace('#[^0-9a-z]+#i', '-', Mage::helper('catalog/product_url')->format($title));
    $url_key = strtolower($url_key);
    $url_key = trim($url_key, '-');
    return $url_key;
  }

  public function getEventUrl($event) {
    $url_path = 'saleevents/' . $event['url_key'] . '.html';
    return Mage::getUrl() . $url_path;
  }

  public function getHappeningEvents() {
    $customerGroup = Mage::getSingleton('customer/session')->getCustomerGroupId();
    $rulePrivate = Mage::getModel('privatesale/event')->getCollection();
    $todayStartOfDayDate = Mage::app()->getLocale()->date()->setTime('00:00:00')->toString(Varien_Date::DATETIME_INTERNAL_FORMAT);

    $todayEndOfDayDate = Mage::app()->getLocale()->date()->setTime('23:59:59')->toString(Varien_Date::DATETIME_INTERNAL_FORMAT);

    $rulePrivate->addFieldToFilter('start_date', array('or' => array(0 => array('date' => TRUE, 'to' => $todayEndOfDayDate), 1 => array('is' => new Zend_Db_Expr('null')))), 'left')->addFieldToFilter('end_date', array('or' => array(0 => array('date' => TRUE, 'from' => $todayStartOfDayDate), 1 => array('is' => new Zend_Db_Expr('null')))), 'left');

    foreach ($rulePrivate as $key => $value) {
      if (!in_array($customerGroup, unserialize($value->getCustomerGroupIds()))) {
        $rulePrivate->removeItemByKey($key);
      }
    }

    return $rulePrivate;
  }

  public function getListCategoryRestricted($customerGroup) {
    $customerGroup = Mage::getSingleton('customer/session')->getCustomerGroupId();
    $query = "SELECT `category_id` FROM " . $this->_getTableName('privatesale_category') . " WHERE `group`=" . $customerGroup;
    $catIds = $this->_getReadConnection()->fetchCol($query);
    return $catIds;
  }

  public function getListProductRestricted($customerGroup) {
    $query = "SELECT `product_id` FROM " . $this->_getTableName('privatesale_product') . " WHERE `group`=" . $customerGroup;
    $results = $this->_getReadConnection()->fetchCol($query);
    return $results;
  }

  public function getListProductByCategory($customerGroup) {
    $categoryids = $this->getListCategoryRestricted($customerGroup);
    $productIds = array();
    $productCollection = Mage::getResourceModel('catalog/product_collection')->joinField('category_id', $this->_getTableName('catalog/category_product'), 'category_id', 'product_id=entity_id', null, 'left')->addAttributeToFilter('category_id', array('in' => $categoryids))->addAttributeToSelect('*');
    $productCollection->getSelect()->group("entity_id");
    $productCollection->load();
    if (count($productCollection->getData()) > 0) {
      $productIds = $productCollection->getColumnValues('entity_id');
    }
    if (count($productIds) > 0) {
      $productIds = implode(',', $productIds);
    }
    return $productIds;
  }

  protected function _getTableName($name) {
    return Mage::getSingleton('core/resource')->getTableName($name);
  }

  protected function _getReadConnection() {
    return Mage::getSingleton('core/resource')->getConnection('core_read');
  }

  protected function _getWriteConnection() {
    return Mage::getSingleton('core/resource')->getConnection('core_write');
  }

  public function getTimeGap($firstTime, $lastTime) {
    $firstTime = strtotime($firstTime);
    $lastTime = strtotime($lastTime);
    $timeGap = $firstTime - $lastTime;
    return $timeGap;
  }

	public function isRequiredInvitatationCode() {
		return (bool) Mage::getStoreConfig('privatesalesession/privatesale_options/open_sign_up', Mage::app()->getStore()->getId());
	}

}