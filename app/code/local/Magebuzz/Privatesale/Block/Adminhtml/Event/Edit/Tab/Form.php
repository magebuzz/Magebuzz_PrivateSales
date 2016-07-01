<?php

	/*
	* @copyright Copyright (c) 2016 www.magebuzz.com
	*/

	class Magebuzz_Privatesale_Block_Adminhtml_Event_Edit_Tab_Form extends Mage_Adminhtml_Block_Widget_Form
	{
		protected function _prepareForm()
		{
			$form = new Varien_Data_Form();
			$this->setForm($form);
			$customerGroups = Mage::getResourceModel('customer/group_collection')->load()->toOptionArray();
			$found = FALSE;

			foreach ($customerGroups as $group) {
				if ($group['value'] == 0) {
					$found = TRUE;
				}
			}
			if (!$found) {
				array_unshift($customerGroups, array('value' => 0, 'label' => Mage::helper('bannerads')->__('NOT LOGGED IN')));
			}
			$fieldset = $form->addFieldset('privatesale_form', array('legend' => Mage::helper('privatesale')->__('Event information')));
			$dateFormatIso = Mage::app()->getLocale()->getDateTimeFormat(Mage_Core_Model_Locale::FORMAT_TYPE_MEDIUM);

			$fieldset->addField('title', 'text', array('label' => Mage::helper('privatesale')->__('Title'), 'class' => 'required-entry', 'required' => TRUE, 'name' => 'title',));
			$fieldset->addField('customer_group_ids', 'multiselect', array('name' => 'customer_group_ids[]', 'label' => Mage::helper('privatesale')->__('Customer Groups'), 'title' => Mage::helper('privatesale')->__('Customer Groups'), 'required' => TRUE, 'values' => Mage::getResourceModel('customer/group_collection')->toOptionArray(),));
			$fieldset->addField('start_date', 'date', array('class' => 'required-entry', 'required' => TRUE, 'name' => 'start_date', 'label' => Mage::helper('privatesale')->__('Start Date'), 'title' => Mage::helper('privatesale')->__('Start Date'), 'image' => $this->getSkinUrl('images/grid-cal.gif'), 'input_format' => Varien_Date::DATETIME_INTERNAL_FORMAT, 'format' => 'yyyy-MM-dd HH:mm:ss', 'time' => TRUE,));
			$fieldset->addField('end_date', 'date', array('class' => 'required-entry', 'required' => TRUE, 'name' => 'end_date', 'label' => Mage::helper('privatesale')->__('End Date'), 'title' => Mage::helper('privatesale')->__('End Date'), 'image' => $this->getSkinUrl('images/grid-cal.gif'), 'input_format' => Varien_Date::DATETIME_INTERNAL_FORMAT, 'format' => 'yyyy-MM-dd HH:mm:ss', 'time' => TRUE,));

			$fieldset->addField('image', 'image', array('label' => Mage::helper('privatesale')->__('Image'), 'name' => 'image',));

			$fieldset->addField('note', 'textarea', array('label' => Mage::helper('privatesale')->__('Note'), 'name' => 'note', 'after_element_html' => '<br/>Write something about this event.'));

			$discountFieldSet = $form->addFieldset('privatesale_form_discount', array('legend' => Mage::helper('privatesale')->__('Discount information')));
			$discountFieldSet->addField('discount_amount', 'text', array('label' => Mage::helper('privatesale')->__('Discount Amount'), 'name' => 'discount_amount', 'class' => 'validate-persent-discount', 'required' => TRUE, 'after_element_html' => '<br/>Enter number of percentage to give discount for products belonging to this event.'));
			$dataForm = null;
			if (Mage::getSingleton('adminhtml/session')->getPrivatesaleEvent()) {
				$dataForm = Mage::getSingleton('adminhtml/session')->getPrivatesaleEvent();
				Mage::getSingleton('adminhtml/session')->setPrivatesaleData(null);
			} elseif (Mage::registry('privatesale_event')) {
				$dataForm = Mage::registry('privatesale_event')->getData();
			}
			if (isset($dataForm['image']) && $dataForm['image'] != '') {
				$dataForm['image'] = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . 'privatesales/events/' . $dataForm['image'];
			}
			$form->setValues($dataForm);
			return parent::_prepareForm();
		}
	}