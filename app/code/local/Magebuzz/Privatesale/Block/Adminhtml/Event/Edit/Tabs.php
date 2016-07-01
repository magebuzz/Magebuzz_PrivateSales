<?php
/*
* @copyright Copyright (c) 2016 www.magebuzz.com
*/

class Magebuzz_Privatesale_Block_Adminhtml_Event_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs {
  public function __construct() {
    parent::__construct();
    $this->setId('privatesale_event_tabs');
    $this->setDestElementId('edit_form');
    $this->setTitle(Mage::helper('privatesale')->__('Events Information'));
  }

  protected function _beforeToHtml() {
    $this->addTab('form_section', array('label' => Mage::helper('privatesale')->__('Events Information'), 'title' => Mage::helper('privatesale')->__('Events Information'), 'content' => $this->getLayout()->createBlock('privatesale/adminhtml_event_edit_tab_form')->toHtml(),));

    $this->addTab('form_section_category', 
			array(
				'label' => Mage::helper('privatesale')->__('Products'), 
				'title' => Mage::helper('privatesale')->__('Products'), 
				'content' => $this->getLayout()->createBlock('privatesale/adminhtml_event_edit_tab_conditions')->toHtml()
			)
		);

    return parent::_beforeToHtml();
  }
}