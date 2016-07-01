<?php

	/*
	* @copyright Copyright (c) 2016 www.magebuzz.com
	*/

	class Magebuzz_Privatesale_Block_Adminhtml_Event_Grid extends Mage_Adminhtml_Block_Widget_Grid
	{
		public function __construct()
		{
			parent::__construct();
			$this->setId('privatesaleGrid');
			$this->setDefaultSort('privatesale_id');
			$this->setDefaultDir('ASC');
			$this->setSaveParametersInSession(TRUE);
		}

		private function changeStatus($start, $end)
		{
			$startTime = Mage::app()->getLocale()->date($start, Varien_Date::DATETIME_INTERNAL_FORMAT, null, FALSE);
			$endTime = Mage::app()->getLocale()->date($end, Varien_Date::DATETIME_INTERNAL_FORMAT, null, FALSE);
			$now = Mage::app()->getLocale()->date(Mage::getModel('core/date')->date(), Varien_Date::DATETIME_INTERNAL_FORMAT, null, FALSE);
			$isStarted = $now->compare($startTime);
			$isExpired = $now->compare($endTime);
			if ($isExpired > 0) return 3; elseif ($isStarted >= 0) return 2;
			else return 1;
		}

		private function update($item)
		{
			$data = $item->getData();
			$item->setData('status', $this->changeStatus($data['start_date'], $data['end_date']));
		}

		protected function _prepareCollection()
		{
			$collection = Mage::getModel('privatesale/event')->getCollection();
			$model = Mage::getModel('privatesale/event');
			foreach ($collection as $item) {
				$this->update($item);
				$model->setData($item->getData())->save();
			}
			$this->setCollection($collection);
			return parent::_prepareCollection();
		}

		protected function _prepareColumns()
		{
			$this->addColumn('event_id', array('header' => Mage::helper('privatesale')->__('ID'), 'index' => 'event_id', 'width' => '50'));

			$this->addColumn('title', array('header' => Mage::helper('privatesale')->__('Title'), 'index' => 'title',));

			$this->addColumn('start_date', array('header' => Mage::helper('privatesale')->__('Start Date'), 'index' => 'start_date',));

			$this->addColumn('end_date', array('header' => Mage::helper('privatesale')->__('End Date'), 'index' => 'end_date',));
			$this->addColumn('customer_group_ids', array('header' => Mage::helper('privatesale')->__('Customer Group'), 'align' => 'left', 'index' => 'customer_group_ids', 'renderer' => 'Magebuzz_Privatesale_Block_Adminhtml_Event_Renderer_Customergroup'));

			$this->addColumn('status', array('header' => Mage::helper('privatesale')->__('Status'), 'width' => '80px', 'index' => 'status', 'type' => 'options', 'options' => array(1 => 'Upcomming', 2 => 'Happening', 3 => 'Expired'),));

			$this->addColumn('action', array('header' => Mage::helper('privatesale')->__('Action'), 'width' => '100', 'type' => 'action', 'getter' => 'getId', 'actions' => array(array('caption' => Mage::helper('privatesale')->__('Edit'), 'url' => array('base' => '*/*/edit'), 'field' => 'id')), 'filter' => FALSE, 'sortable' => FALSE, 'index' => 'stores', 'is_system' => TRUE,));

			return parent::_prepareColumns();
		}

		protected function _prepareMassaction()
		{
			$this->setMassactionIdField('privatesale_id');
			$this->getMassactionBlock()->setFormFieldName('privatesale');

			$this->getMassactionBlock()->addItem('delete', array('label' => Mage::helper('privatesale')->__('Delete'), 'url' => $this->getUrl('*/*/massDelete'), 'confirm' => Mage::helper('privatesale')->__('Are you sure?')));
			return $this;
		}

		public function getRowUrl($row)
		{
			return $this->getUrl('*/*/edit', array('id' => $row->getId()));
		}

	}