<?php
/*
* @copyright Copyright (c) 2016 www.magebuzz.com
*/

class Magebuzz_Privatesale_Block_Adminhtml_Event_Categoryattributes extends Mage_Adminhtml_Block_Catalog_Category_Tab_Attributes {
  protected function _prepareForm() {
    $group = $this->getGroup();
    $attributes = $this->getAttributes();

    $form = new Varien_Data_Form();
    $form->setHtmlIdPrefix('group_' . $group->getId());
    $form->setDataObject($this->getCategory());

    $fieldset = $form->addFieldset('fieldset_group_' . $group->getId(), array('legend' => Mage::helper('catalog')->__($group->getAttributeGroupName()), 'class' => 'fieldset-wide',));

    if ($this->getAddHiddenFields()) {
      if (!$this->getCategory()->getId()) {
        // path
        if ($this->getRequest()->getParam('parent')) {
          $fieldset->addField('path', 'hidden', array('name' => 'path', 'value' => $this->getRequest()->getParam('parent')));
        } else {
          $fieldset->addField('path', 'hidden', array('name' => 'path', 'value' => 1));
        }
      } else {
        $fieldset->addField('id', 'hidden', array('name' => 'id', 'value' => $this->getCategory()->getId()));
        $fieldset->addField('path', 'hidden', array('name' => 'path', 'value' => $this->getCategory()->getPath()));
      }
    }

    $this->_setFieldset($attributes, $fieldset);

    foreach ($attributes as $attribute) {
      /* @var $attribute Mage_Eav_Model_Entity_Attribute */
      if ($attribute->getAttributeCode() == 'url_key') {
        if ($this->getCategory()->getLevel() == 1) {
          $fieldset->removeField('url_key');
          $fieldset->addField('url_key', 'hidden', array('name' => 'url_key', 'value' => $this->getCategory()->getUrlKey()));
        } else {
          $form->getElement('url_key')->setRenderer($this->getLayout()->createBlock('adminhtml/catalog_form_renderer_attribute_urlkey'));
        }
      }
    }

    if ($this->getCategory()->getLevel() == 1) {
      $fieldset->removeField('custom_use_parent_settings');
    } else {
      if ($this->getCategory()->getCustomUseParentSettings()) {
        foreach ($this->getCategory()->getDesignAttributes() as $attribute) {
          if ($element = $form->getElement($attribute->getAttributeCode())) {
            $element->setDisabled(TRUE);
          }
        }
      }
      if ($element = $form->getElement('custom_use_parent_settings')) {
        $element->setData('onchange', 'onCustomUseParentChanged(this)');
      }
    }

    if ($this->getCategory()->hasLockedAttributes()) {
      foreach ($this->getCategory()->getLockedAttributes() as $attribute) {
        if ($element = $form->getElement($attribute)) {
          $element->setReadonly(TRUE, TRUE);
        }
      }
    }

    if (!$this->getCategory()->getId()) {
      $this->getCategory()->setIncludeInMenu(1);
    }

    $values = $this->getCategory()->getData();

    if ($group->getAttributeGroupName() == 'General Information') {
      $groups = Mage::getModel('customer/group')->getCollection()->getData();
      $multiselectField = array();
      foreach ($groups as $group) {
        $temp = array('customer_group_id' => $group['customer_group_id'], 'customer_group_code' => $group['customer_group_code'], 'value' => $group['customer_group_id'], 'label' => $group['customer_group_code']);
        array_push($multiselectField, $temp);
      }
      array_push($multiselectField, array('value' => -1, 'label' => 'None'));
      $fieldset->addField('groups', 'multiselect', array('label' => 'Restricted Groups', 'name' => 'groups', 'values' => $multiselectField, 'after_element_html' => '<br/> *Press Ctrl for Multiselect.<br/> If select none, please don\'t choose others.'));
      $collection = Mage::getModel('privatesale/category')->getCollection();
      $collection->addFieldToFilter('category_id', Mage::registry('category')->getId());
      $data = $collection->getData();
      $s = null;
      foreach ($data as $item) {
        $s .= $item['group'] . ',';
      }
      $selected = array('groups' => $s);
      $values = array_merge($values, $selected);
    }

    $form->addValues($values);
    Mage::dispatchEvent('adminhtml_catalog_category_edit_prepare_form', array('form' => $form));
    $form->setFieldNameSuffix('general');
    $this->setForm($form);
  }
}