<?php
/*
* Copyright (c) 2016 www.magebuzz.com
*/

class Magebuzz_Privatesale_Model_Event extends Mage_Core_Model_Abstract {
	public function _construct() {
		parent::_construct();
		$this->_init('privatesale/event');
	}

	protected function _afterLoad() {
		$this->setCustomerGroupIds(unserialize($this->getCustomerGroupIds()));
	}

	/**
	* Prepare data before saving
	*
	* @return Mage_Rule_Model_Abstract
	*/
	protected function _beforeSave()
	{
		parent::_beforeSave();
		if ($this->hasCustomerGroupIds()) {
		    $groupIds = $this->getCustomerGroupIds();
		    if (is_array($groupIds) && !empty($groupIds)) {
		        $this->setCustomerGroupIds(serialize($groupIds));
		    }
		}
		return $this;
	}
}