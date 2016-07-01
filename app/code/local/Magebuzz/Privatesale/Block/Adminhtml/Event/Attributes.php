<?php
/*
* @copyright Copyright (c) 2016 www.magebuzz.com
*/

class Magebuzz_Privatesale_Block_Adminhtml_Event_Attributes extends Mage_Adminhtml_Block_Catalog_Product_Edit_Tab_Attributes {
  protected function _prepareForm() {
    $group = $this->getGroup();
    if ($group) {
      $form = new Varien_Data_Form();
      $form->setDataObject(Mage::registry('product'));
      $fieldset = $form->addFieldset('group_fields' . $group->getId(), array('legend' => Mage::helper('catalog')->__($group->getAttributeGroupName()), 'class' => 'fieldset-wide'));
      $attributes = $this->getGroupAttributes();
      $this->_setFieldset($attributes, $fieldset, array('gallery'));
      $urlKey = $form->getElement('url_key');
      if ($urlKey) {
        $urlKey->setRenderer($this->getLayout()->createBlock('adminhtml/catalog_form_renderer_attribute_urlkey'));
      }
      $tierPrice = $form->getElement('tier_price');
      if ($tierPrice) {
        $tierPrice->setRenderer($this->getLayout()->createBlock('adminhtml/catalog_product_edit_tab_price_tier'));
      }
      $groupPrice = $form->getElement('group_price');
      if ($groupPrice) {
        $groupPrice->setRenderer($this->getLayout()->createBlock('adminhtml/catalog_product_edit_tab_price_group'));
      }
      $recurringProfile = $form->getElement('recurring_profile');
      if ($recurringProfile) {
        $recurringProfile->setRenderer($this->getLayout()->createBlock('adminhtml/catalog_product_edit_tab_price_recurring'));
      }
      if (!$form->getElement('media_gallery') && Mage::getSingleton('admin/session')->isAllowed('catalog/attributes/attributes')
      ) {
        $headerBar = $this->getLayout()->createBlock('adminhtml/catalog_product_edit_tab_attributes_create');

        $headerBar->getConfig()->setTabId('group_' . $group->getId())->setGroupId($group->getId())->setStoreId($form->getDataObject()->getStoreId())->setAttributeSetId($form->getDataObject()->getAttributeSetId())->setTypeId($form->getDataObject()->getTypeId())->setProductId($form->getDataObject()->getId());

        $fieldset->setHeaderBar($headerBar->toHtml());
      }
      if ($form->getElement('meta_description')) {
        $form->getElement('meta_description')->setOnkeyup('checkMaxLength(this, 255);');
      }
      $values = Mage::registry('product')->getData();
      if (!Mage::registry('product')->getId()) {
        foreach ($attributes as $attribute) {
          if (!isset($values[$attribute->getAttributeCode()])) {
            $values[$attribute->getAttributeCode()] = $attribute->getDefaultValue();
          }
        }
      }
      if (Mage::registry('product')->hasLockedAttributes()) {
        foreach (Mage::registry('product')->getLockedAttributes() as $attribute) {
          $element = $form->getElement($attribute);
          if ($element) {
            $element->setReadonly(TRUE, TRUE);
          }
        }
      }

      if ($group->getAttributeGroupName() == 'General') {
        $customer_groups = Mage::getModel('customer/group')->getCollection()->getData();
        $multiselectField = array();
        foreach ($customer_groups as $customer_group) {
          $temp = array('customer_group_id' => $customer_group['customer_group_id'], 'customer_group_code' => $customer_group['customer_group_code'], 'value' => $customer_group['customer_group_id'], 'label' => $customer_group['customer_group_code']);
          array_push($multiselectField, $temp);
        }
        array_push($multiselectField, array('value' => -1, 'label' => 'None'));
        $fieldset->addField('groups', 'multiselect', array('label' => 'Restricted Groups', 'name' => 'groups', 'values' => $multiselectField, 'after_element_html' => '<br/> *Press Ctrl for Multiselect.<br/> If select none, please don\'t choose others.'));
        $collection = Mage::getModel('privatesale/product')->getCollection();
        $collection->addFieldToFilter('product_id', Mage::registry('product')->getId());
        $data = $collection->getData();
        $s = null;
        foreach ($data as $item) {
          $s .= $item['group'] . ',';
        }
        $selected = array('groups' => $s);
        $values = array_merge($values, $selected);
      }
      $form->addValues($values);
      $form->setFieldNameSuffix('product');
      Mage::dispatchEvent('adminhtml_catalog_product_edit_prepare_form', array('form' => $form));
      $this->setForm($form);
    }
  }
}