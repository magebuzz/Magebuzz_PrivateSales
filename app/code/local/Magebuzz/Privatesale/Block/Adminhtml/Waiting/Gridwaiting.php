<?php
/*
* @copyright Copyright (c) 2016 www.magebuzz.com
*/

class Magebuzz_Privatesale_Block_Adminhtml_Waiting_Gridwaiting extends Mage_Adminhtml_Block_Widget_Grid {
  public function __construct() {
    parent::__construct();
    $this->setId('waitingGrid');
    $this->setDefaultSort('waiting_id');
    $this->setDefaultDir('ASC');
    $this->setSaveParametersInSession(TRUE);
  }

  protected function _prepareCollection() {
    $collection = Mage::getModel('privatesale/waiting')->getCollection();
    $this->setCollection($collection);
    return parent::_prepareCollection();
  }

  protected function _prepareColumns() {
    $this->addColumn('waiting_id', array('header' => Mage::helper('privatesale')->__('ID'), 'index' => 'waiting_id', 'width' => '80px'));

    $this->addColumn('name_waiting', array('header' => Mage::helper('privatesale')->__('Name'), 'index' => 'name_waiting',));

    $this->addColumn('email_waiting', array('header' => Mage::helper('privatesale')->__('Email'), 'index' => 'email_waiting',));

    $groups = Magebuzz_Privatesale_Model_Statuswaiting::getOptionArray();

    $this->addColumn('status', array('header' => Mage::helper('privatesale')->__('Status'), 'width' => '100', 'index' => 'status', 'type' => 'options', 'options' => $groups,));

    $this->addColumn('action', array('header' => Mage::helper('privatesale')->__('Action'), 'width' => '100', 'type' => 'action', 'getter' => 'getId', 'actions' => array(array('caption' => Mage::helper('privatesale')->__('Send Code'), 'url' => array('base' => '*/*/sendcode'), 'field' => 'id')), 'filter' => FALSE, 'sortable' => FALSE, 'index' => 'stores', 'is_system' => TRUE,));

    return parent::_prepareColumns();
  }

  protected function _prepareMassaction() {
    $this->setMassactionIdField('waiting_id');
    $this->getMassactionBlock()->setFormFieldName('waiting_id');

    $this->getMassactionBlock()->addItem('delete', array('label' => Mage::helper('privatesale')->__('Delete'), 'url' => $this->getUrl('*/*/massDelete'), 'confirm' => Mage::helper('privatesale')->__('Are you sure?')));

    $statuses = Mage::getSingleton('privatesale/statuswaiting')->getOptionArray();

    array_unshift($statuses, array('label' => '', 'value' => ''));
    $this->getMassactionBlock()->addItem('status', array('label' => Mage::helper('privatesale')->__('Change status'), 'url' => $this->getUrl('*/*/massStatus', array('_current' => TRUE)), 'additional' => array('visibility' => array('name' => 'status', 'type' => 'select', 'class' => 'required-entry', 'label' => Mage::helper('privatesale')->__('Status'), 'values' => $statuses))));
    return $this;
  }

}