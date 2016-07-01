<?php
/*
* Copyright (c) 2016 www.magebuzz.com
*/

class Magebuzz_Privatesale_Model_Catalog_Rule extends Mage_CatalogRule_Model_Rule {

  public function _construct() {
    parent::_construct();
    $this->_init('privatesale/event');
  }

  public function getConditionsInstance() {
    return Mage::getModel('catalogrule/rule_condition_combine');
  }
  /**
   * Prepare data before saving
   *
   * @return Mage_Rule_Model_Abstract
   */
  protected function _beforeSave()
  {
    parent::_beforeSave();
    if ($this->hasCustomerGroupIds()) {
        $groupIds = $this->getCustomerGroupIds();
        if (is_array($groupIds) && !empty($groupIds)) {
            $this->setCustomerGroupIds(serialize($groupIds));
        }
    }
    return $this;
  }

  /**
   * Getter for rule actions collection
   *
   * @return Mage_CatalogRule_Model_Rule_Action_Collection
   */
  public function getActionsInstance() {
    return Mage::getModel('catalogrule/rule_action_collection');
  }

  /**
   * Get catalog rule customer group Ids
   *
   * @return array
   */

  public function getMatchingProductIds() {
    if (is_null($this->_productIds)) {
      $this->_productIds = array();
      $this->setCollectedAttributes(array());
      $productCollection = Mage::getResourceModel('catalog/product_collection');
      if ($this->_productsFilter) {
        $productCollection->addIdFilter($this->_productsFilter);
      }
      $this->getConditions()->collectValidatedAttributes($productCollection);

      Mage::getSingleton('core/resource_iterator')->walk($productCollection->getSelect(), array(array($this, 'callbackValidateProduct')), array('attributes' => $this->getCollectedAttributes(), 'product' => Mage::getModel('catalog/product'),));
    }

    return $this->_productIds;
  }

  public function callbackValidateProduct($args) {
    $product = clone $args['product'];
    $product->setData($args['row']);

    $results = 0;
    //foreach ($this->_getWebsitesMap() as $websiteId => $defaultStoreId) {
    //    $product->setStoreId($defaultStoreId);
    $results = (int)$this->getConditions()->validate($product);
    //}
    if ($results > 0) {
      $this->_productIds[] = $product->getId();
    }
  }
}
