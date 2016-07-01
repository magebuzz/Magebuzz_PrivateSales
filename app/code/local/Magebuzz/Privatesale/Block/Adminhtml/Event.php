<?php
/*
* @copyright Copyright (c) 2016 www.magebuzz.com
*/

class Magebuzz_Privatesale_Block_Adminhtml_Event extends Mage_Adminhtml_Block_Widget_Grid_Container {
  public function __construct() {
    $this->_controller = 'adminhtml_event';
    $this->_blockGroup = 'privatesale';
    $this->_headerText = Mage::helper('privatesale')->__('Sales Event');
    $this->_addButtonLabel = Mage::helper('privatesale')->__('Add Event');
    parent::__construct();
  }
}