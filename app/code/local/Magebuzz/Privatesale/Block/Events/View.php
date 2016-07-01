<?php
/*
* @copyright Copyright (c) 2016 www.magebuzz.com
*/

class Magebuzz_Privatesale_Block_Events_View extends Mage_Core_Block_Template {

  protected $_productCollection;

  public function _prepareLayout() {
    $breadcrumbs = $this->getLayout()->getBlock('breadcrumbs');
    $breadcrumbs->addCrumb('home', array('label' => Mage::helper('cms')->__('Home'), 'title' => Mage::helper('cms')->__('Home Page'), 'link' => Mage::getBaseUrl()));

    $breadcrumbs->addCrumb('sale_events', array('label' => Mage::helper('privatesale')->__('Sale Events'), 'title' => Mage::helper('privatesale')->__('Sale Events'), 'link' => Mage::getBaseUrl() . 'privatesale'));

    $breadcrumbs->addCrumb('event', array('label' => $this->getEvent()->getTitle(), 'title' => $this->getEvent()->getTitle()));
    return parent::_prepareLayout();
  }

  public function getEvent() {
    return Mage::registry('current_event');
  }

  public function getCurrentShowEvent() {
    return $this->getEvent();
  }

  public function setListCollection() {
    $this->getChild('sale_private_list')->setCollection($this->_getProductCollection());
  }

  protected function _getProductCollection() {

    $event = $this->getEvent();
    $collection = null;
    $currentTime = now();
    $collection = Mage::getModel('catalog/product')->getCollection()->addAttributeToSelect('*');
    $startTime = Mage::helper('privatesale')->getTimeGap($currentTime, $event->getStartDate());
    $endTime = Mage::helper('privatesale')->getTimeGap($event->getEndDate(), $currentTime);
    if ($startTime >= 0 && $endTime > 0) {
      $rule = Mage::getModel('privatesale/catalog_rule')->load($event->getEventId());
      $productIds = $rule->getMatchingProductIds();
      $collection->addAttributeToFilter('entity_id', array('in' => $productIds));
      $collection->setVisibility(Mage::getSingleton('catalog/product_visibility')->getVisibleInCatalogIds());
    } else {
      $collection->addAttributeToFilter('entity_id', array('eq' => 'NULL'));
    }
    return $collection;
  }

  protected function _isHappening() {
    $event = $this->getEvent();
    $currentTime = now();
    $startTime = Mage::helper('privatesale')->getTimeGap($currentTime, $event->getStartDate());
    $endTime = Mage::helper('privatesale')->getTimeGap($event->getEndDate(), $currentTime);
    if ($startTime >= 0 && $endTime > 0) {
      return TRUE;
    } else {
      return FALSE;
    }
  }

}