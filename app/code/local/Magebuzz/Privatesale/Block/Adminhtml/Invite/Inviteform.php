<?php
/*
* @copyright   Copyright (c) 2016 www.magebuzz.com
*/

class Magebuzz_Privatesale_Block_Adminhtml_Invite_Inviteform extends Mage_Adminhtml_Block_Widget_Form {
  protected function _prepareForm() {
    $form = new Varien_Data_Form(array('id' => 'edit_form', 'action' => $this->getUrl('*/*/invite'), 'method' => 'post', 'enctype' => 'multipart/form-data',));
    $form->setUseContainer(TRUE);
    $this->setForm($form);
    $fieldset = $form->addFieldset('myform_form', array('legend' => Mage::helper('privatesale')->__('Invite Customer')));
    $fieldset->addField('email', 'text', array('label' => Mage::helper('privatesale')->__('Email'), 'class' => 'input-text required-entry validate-email', 'required' => TRUE, 'name' => 'email',));
    $fieldset->addField('message', 'textarea', array('label' => Mage::helper('privatesale')->__('Invite Message'), 'name' => 'message',));
    return parent::_prepareForm();
  }
}