<?php
/*
* @copyright Copyright (c) 2016 www.magebuzz.com
*/

class Magebuzz_Privatesale_Block_Adminhtml_Event_Edit_Tab_Conditions extends Mage_Adminhtml_Block_Widget_Form implements Mage_Adminhtml_Block_Widget_Tab_Interface {

  public function getTabLabel() {
    return Mage::helper('catalogrule')->__('Conditions');
  }

  public function getTabTitle() {
    return Mage::helper('catalogrule')->__('Conditions');
  }

  public function canShowTab() {
    return TRUE;
  }

  public function isHidden() {
    return FALSE;
  }

  protected function _prepareForm() {
    $id = $this->getRequest()->getParam('id');
    $model = Mage::getModel('privatesale/catalog_rule')->load($id);
    // Zend_Debug::dump($model->getData());die();
    $form = new Varien_Data_Form();
    $model->getConditions()->setJsFormObject('rule_conditions_fieldset');
    $form->setHtmlIdPrefix('rule_');

    $renderer = Mage::getBlockSingleton('adminhtml/widget_form_renderer_fieldset')->setTemplate('promo/fieldset.phtml')->setNewChildUrl($this->getUrl('*/privatesale_event/newConditionHtml/form/rule_conditions_fieldset'));

    $fieldset = $form->addFieldset('conditions_fieldset', array('legend' => Mage::helper('catalogrule')->__('Conditions (leave blank for all products)')))->setRenderer($renderer);

    $fieldset->addField('conditions', 'text',
			array(
				'name' => 'conditions',
				'label' => Mage::helper('catalogrule')->__('Conditions'),
				'title' => Mage::helper('catalogrule')->__('Conditions'),
				'required' => TRUE
			))->setRule($model)->setRenderer(Mage::getBlockSingleton('rule/conditions'));

    $form->setValues($model->getData());
    $this->setForm($form);

    return parent::_prepareForm();
  }
}
