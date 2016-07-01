<?php
/*
* @copyright Copyright (c) 2016 www.magebuzz.com
*/

class Magebuzz_Privatesale_Block_Adminhtml_Event_Edit extends Mage_Adminhtml_Block_Widget_Form_Container {
  public function __construct() {
    parent::__construct();
    $this->_objectId = 'id';
    $this->_blockGroup = 'privatesale';
    $this->_controller = 'adminhtml_event';

    $this->_updateButton('save', 'label', Mage::helper('privatesale')->__('Save Item'));
    $this->_updateButton('delete', 'label', Mage::helper('privatesale')->__('Delete Item'));

    $this->_addButton('saveandcontinue', array('label' => Mage::helper('adminhtml')->__('Save And Continue Edit'), 'onclick' => 'saveAndContinueEdit()', 'class' => 'save',), -100);

    $this->_formScripts[] = "
    function toggleEditor() {
    if (tinyMCE.getInstanceById('privatesale_content') == null) {
    tinyMCE.execCommand('mceAddControl', false, 'privatesale_content');
    } else {
    tinyMCE.execCommand('mceRemoveControl', false, 'privatesale_content');
    }
    }
    
    Validation.add('validate-persent-discount','Please input a number greater than 0 and less than 100 in this field',function(field_value){
    if(field_value >0 && field_value <=100)
    {
    return true;
    }
    return false;
    });
    
    function saveAndContinueEdit(){
    editForm.submit($('edit_form').action+'back/edit/');
    }
    ";
  }

  public function getHeaderText() {
    if (Mage::registry('privatesale_event') && Mage::registry('privatesale_event')->getId()) {
      return Mage::helper('privatesale')->__("Edit Sales Event '%s'", $this->htmlEscape(Mage::registry('privatesale_event')->getTitle()));
    } else {
      return Mage::helper('privatesale')->__('Add Event');
    }
  }
}