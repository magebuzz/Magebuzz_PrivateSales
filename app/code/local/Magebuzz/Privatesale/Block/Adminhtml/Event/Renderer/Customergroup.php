<?php

class Magebuzz_Privatesale_Block_Adminhtml_Event_Renderer_Customergroup extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
	protected $_newCustomerArr;

	public function render(Varien_Object $row)
	{
		$getData = $row->getData();
		$customerArray = Mage::getResourceModel('customer/group_collection')->toOptionArray();
		$newCustomerArr = $this->_newCustomerArr;

		if (!$newCustomerArr) {
			foreach ($customerArray as $key => $value) {
				$newCustomerArr[$value['value']] = $value;
			}
			$this->_newCustomerArr = $newCustomerArr;
		}

		$customerGroupId = unserialize($getData['customer_group_ids']);

		$customer = '';
		foreach ($customerGroupId as $key => $value) {
			if ($value == count($customerGroupId) - 1) {
				if (isset($newCustomerArr[$value]['label'])) $customer .= $newCustomerArr[$value]['label'];
			} else {
				if (isset($newCustomerArr[$value]['label'])) $customer .= $newCustomerArr[$value]['label'] . ',';
			}
		}

		return $customer;
	}
}