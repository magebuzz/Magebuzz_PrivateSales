<?php

	/*
	* @copyright Copyright (c) 2016 www.magebuzz.com
	*/

	class Magebuzz_Privatesale_Model_Observer
	{
		const XML_PATH_CUSTOMER_APPROVAL_EMAIL_TEMPLATE = 'privatesalesession/privatesale_options/approval_notification_email_template';
		const XML_PATH_CUSTOMER_REJECT_EMAIL_TEMPLATE = 'privatesalesession/privatesale_options/rejection_notification_email_template';

		public function controllerPredispatchAction($observer)
		{
			// check login before view site
			$isForcedToLogin = Mage::getStoreConfig('privatesalesession/privatesale_options/force_user_to_login');
			$isRedirectLanding = Mage::getStoreConfig('privatesalesession/privatesale_options/redirect_to_landing');
			$identifier = Mage::getStoreConfig('privatesalesession/privatesale_options/cms_landing_page');
			$fullActionName = $observer->getEvent()->getControllerAction()->getFullActionName();
			$request = Mage::app()->getRequest();
			$module = $request->getModuleName();
			$controller = $request->getControllerName();
			$pathInfo = $request->getPathInfo();
			if ($module == 'cms' && ($pathInfo == '/' . $identifier || $pathInfo == '/' . $identifier . '/')) {
				if (Mage::getSingleton('customer/session')->isLoggedIn()) {
					Mage::app()->getFrontController()->getResponse()->setRedirect(Mage::getUrl());
					return;
				}
			}
			if ($isForcedToLogin == '1' && !Mage::helper('customer')->isLoggedIn()) {
				if (($module == 'customer' || $module == 'privatesale') && $controller == 'account' || $fullActionName == 'privatesale_invite_waitinglist' || ($module == 'cms' && ($pathInfo == '/' . $identifier || $pathInfo == '/' . $identifier . '/')) || $fullActionName == 'privatesale_invite_sendwaiting' /*&& $action=='login'*/) {
					return;
				} else {
					if ($isRedirectLanding == 1) {
						Mage::app()->getFrontController()->getResponse()->setRedirect(Mage::getUrl($identifier));
					} else {
						Mage::app()->getFrontController()->getResponse()->setRedirect(Mage::getUrl('customer/account'));
					}
				}
			}
		}

		public function catalogProductSaveAfter($observer)
		{
			$data = Mage::app()->getRequest()->getPost('product');
			$productId = $observer->getProduct()->getId();
			$model = Mage::getModel('privatesale/product');
			$collection = $model->getCollection();
			$collection->addFieldToFilter('product_id', $productId);
			$items = $collection->getData();
			foreach ($items as $item) {
				$model->setId($item['privatesale_id'])->delete();
			}
			if (isset($data['groups'])) {
				$groups = $data['groups'];
				if (!in_array('-1', $groups)) {
					foreach ($groups as $group) {
						$model->setData(array('product_id' => $productId, 'group' => $group))->save();
					}
				}
			}
		}


		public function catalogCategorySaveAfter($observer)
		{
			$data = Mage::app()->getRequest()->getPost('general');
			$groups = $data['groups'];
			$category = $observer->getCategory();
			$collection = Mage::getModel('catalog/category')->load($category->getId())->getProductCollection();
			$model = Mage::getModel('privatesale/product');
			foreach ($collection as $product) {
				$items = $model->getCollection();
				$items->addFieldToFilter('product_id', $product->getId());
				$items = $items->getData();
				foreach ($items as $item) {
					$model->setId($item['privatesale_id'])->delete();
				}
			}
			if (!in_array('-1', $groups)) {
				foreach ($collection as $product) {
					foreach ($groups as $group) {
						$temp = array('product_id' => $product->getId(), 'group' => $group);
						$model->setData($temp)->save();
					}
				}
			}
			$categories = Mage::getModel('catalog/category')->load($category->getId())->getAllChildren(TRUE);
			$model = Mage::getModel('privatesale/category');
			foreach ($categories as $category) {
				$items = $model->getCollection();
				$items->addFieldToFilter('category_id', $category);
				$items = $items->getData();
				foreach ($items as $item) {
					$model->setId($item['privatesale_id'])->delete();
				}
			}
			if (!in_array('-1', $groups)) {
				foreach ($categories as $category) {
					foreach ($groups as $group) {
						$temp = array('category_id' => $category, 'group' => $group);
						$model->setData($temp)->save();
					}
				}
			}
		}

		// Return IDs of category that CAN NOT be seen by current users
		private function getPrivateCategoryIds()
		{
			$customerGroup = Mage::getSingleton('customer/session')->getCustomerGroupId();
			$privateCategoryCollection = Mage::getModel('privatesale/category')->getCollection();
			$allIds = $privateCategoryCollection->getColumnValues('category_id');
			$allowedCategories = $privateCategoryCollection->getItemsByColumnValue('group', $customerGroup);
			$allowedCategoryIds = array();
			foreach ($allowedCategories as $allowedCategory) {
				array_push($allowedCategoryIds, $allowedCategory['category_id']);
			}
			return array_unique(array_diff($allIds, $allowedCategoryIds));
		}

		// Check if product can be viewed by current user
		private function isAllowProduct($product)
		{
			$customerGroup = Mage::getSingleton('customer/session')->getCustomerGroupId();
			$collection = Mage::getModel('privatesale/product')->getCollection();

			$allowGroups = $collection->getItemsByColumnValue('product_id', $product['entity_id']);
			if (empty($allowGroups)) return TRUE;
			foreach ($allowGroups as $allowGroup) {
				if ($allowGroup['group'] == $customerGroup) return TRUE;
			}
			return FALSE;
		}

		// Check if category can be viewed by current user
		private function isAllowCategory($category)
		{
			$customerGroup = Mage::getSingleton('customer/session')->getCustomerGroupId();
			$collection = Mage::getModel('privatesale/category')->getCollection();
			$allowGroups = $collection->getItemsByColumnValue('category_id', $category['entity_id']);
			if (empty($allowGroups)) return TRUE;
			foreach ($allowGroups as $allowGroup) {
				if ($allowGroup['group'] == $customerGroup) return TRUE;
			}
			return FALSE;
		}

		/*
		* Hide category based on customer group restriction
		*/
		public function catalogCategoryCollectionLoadBefore($observer)
		{
			$config = Mage::helper('privatesale')->restrictedOptionConfig();
			if ($config == Magebuzz_Privatesale_Model_Restrictedoptions::HIDE_ITEM) {
				$customerGroup = Mage::getSingleton('customer/session')->getCustomerGroupId();
				$catIds = Mage::helper('privatesale')->getListCategoryRestricted($customerGroup);
				if (!empty($catIds)) {
					$collection = $observer->getEvent()->getCategoryCollection();
					if ($catIds) $collection->addFieldToFilter('entity_id', array('nin' => $catIds));
				}
			}
		}

		/*
		* redirect to home page if this product is hiden for current user
		*/
		public function catalogProductControllerInitAfter($observer)
		{
			$config = Mage::helper('privatesale')->restrictedOptionConfig();
			if ($config == Magebuzz_Privatesale_Model_Restrictedoptions::HIDE_ITEM) {
				$customerGroup = Mage::getSingleton('customer/session')->getCustomerGroupId();
				$product_id = $observer->getEvent()->getProduct()->getId();
				$query = "SELECT * FROM " . $this->_getTableName('privatesale_product') . "
      WHERE `product_id` = " . $product_id . "
      AND `group` = " . $customerGroup . "
      ";
				$result = $this->_getReadConnection()->fetchCol($query);
				if (!empty($result)) {
					$baseUrl = Mage::getBaseUrl();
					Mage::app()->getResponse()->setRedirect($baseUrl);
				}
			}
		}

		/*
		* check if a category can be accessed by current user
		* if not, redirect to home page
		*/

		public function CatalogControllerInitAfter($observer)
		{
			$config = Mage::helper('privatesale')->restrictedOptionConfig();
			if ($config == Magebuzz_Privatesale_Model_Restrictedoptions::HIDE_ITEM) {
				$customerGroup = Mage::getSingleton('customer/session')->getCustomerGroupId();
				$category_id = $observer->getEvent()->getCategory()->getId();
				$query = "SELECT * FROM " . $this->_getTableName('privatesale_category') . "
      WHERE `category_id` = " . $category_id . "
      AND `group` = " . $customerGroup . "
      ";
				$result = $this->_getReadConnection()->fetchCol($query);

				if (!empty($result)) {
					$baseUrl = Mage::getBaseUrl();
					Mage::app()->getResponse()->setRedirect($baseUrl);
				}
			}
		}

		/*
		* hide products based on customer group restriction
		*/
		public function catalogProductCollectionApplyAfter($observer)
		{
			$config = Mage::helper('privatesale')->restrictedOptionConfig();
			if ($config == Magebuzz_Privatesale_Model_Restrictedoptions::HIDE_ITEM) {
				$groupId = Mage::getSingleton('customer/session')->getCustomerGroupId();
				$results = Mage::helper('privatesale')->getListProductRestricted($groupId);
				if (!empty($results)) {
					$collection = $observer->getEvent()->getCollection();
					$collection->addFieldToFilter('entity_id', array('nin' => $results));
				}
			}
		}

		protected function _getTableName($name)
		{
			return Mage::getSingleton('core/resource')->getTableName($name);
		}

		protected function _getReadConnection()
		{
			return Mage::getSingleton('core/resource')->getConnection('core_read');
		}

		protected function _getWriteConnection()
		{
			return Mage::getSingleton('core/resource')->getConnection('core_write');
		}

		//public function checkout_cart_product_add_after($observer) {
		//    $productId = $observer->getEvent()->getProduct();
		//        if (!$this->isAllowProduct($product)) {
		//          Mage::throwException(Mage::helper('checkout')->__("Can't add the product to cart!"));
		//        }
		//    return $this;
		//  }

		public function catalogProductCompareAddProduct($observer)
		{
			$isForcedToLogin = Mage::getStoreConfig('privatesalesession/privatesale_options/force_user_to_login');
			if ($isForcedToLogin == '1' && !Mage::helper('customer')->isLoggedIn()) {
				Mage::getSingleton('catalog/session')->unsetAll();
				Mage::app()->getFrontController()->getResponse()->setRedirect(Mage::getUrl('customer/account'))->sendResponse();
				exit();
			}
		}

		public function checkoutCartSaveBefore($observer)
		{
			$isForcedToLogin = Mage::getStoreConfig('privatesalesession/privatesale_options/force_user_to_login');
			if ($isForcedToLogin == '1' && !Mage::helper('customer')->isLoggedIn()) {
				Mage::app()->getFrontController()->getResponse()->setRedirect(Mage::getUrl('customer/account'))->sendResponse();
				exit();
			}
		}

		public function addAttributeToCustomerForm(Varien_Event_Observer $observer)
		{

			$block = $observer->getEvent()->getBlock();
			if (!isset($block)) {
				return $this;
			}
			$allowPendingCustomer = (int)Mage::getStoreConfig('privatesalesession/privatesale_options/allow_pending_customer');
			if ($allowPendingCustomer == 1) {
				$dataCustomer = Mage::registry('current_customer');
				if ($block->getType() == 'adminhtml/customer_edit_tab_account') {
					$form = $block->getForm();
					$fieldset = $form->getElement('base_fieldset');
					$fieldset->addField('user_status', 'select', array('name' => 'user_status', 'label' => Mage::helper('privatesale')->__('Customer Status'), 'title' => Mage::helper('privatesale')->__('Customer Status'), 'values' => Magebuzz_Privatesale_Model_User::getOptionArray(),));

					if ($dataCustomer->getId()) {
						$privateUser = Mage::getModel('privatesale/user')->loadByUserId($dataCustomer->getId());
						$dataCustomer->setUserStatus($privateUser->getUserStatus());
						$form->setValues($dataCustomer->getData());
					}
					return $block->setForm($form);
				}
			}
		}

		public function coreBlockAbstractPrepareLayoutBefore($observer)
		{
			$block = $observer->getEvent()->getBlock();
			$allowPendingCustomer = (int)Mage::getStoreConfig('privatesalesession/privatesale_options/allow_pending_customer');
			if ($allowPendingCustomer == 1) {
				if ($block->getType() == 'adminhtml/customer_grid') {
					$block->addColumnAfter('user_status', array('header' => 'User Status', 'type' => 'text', 'index' => 'user_status', 'renderer' => 'privatesale/adminhtml_customer_grid_renderer_status', 'filter' => FALSE, 'sort' => FALSE), 'website_id');
				}
			}
		}

		public function customerSaveAfterInBackend($observer)
		{
			$allowPendingCustomer = (int)Mage::getStoreConfig('privatesalesession/privatesale_options/allow_pending_customer');
			if ($allowPendingCustomer == 1) {
				$event = $observer->getEvent();
				$request = $event->getRequest();
				$post = $request->getPost();
				$customer = $event->getCustomer();
				$user = Mage::getModel('privatesale/user')->loadByUserId($customer->getEntityId());
				$emailsender = Mage::getStoreConfig('contacts/email/recipient_email');
				$user->setUserStatus($post['account']['user_status']);
				$firstName = $customer->getFirstname();
				$lastName = $customer->getLastname();
				$dataInfo = array('customer_name' => $firstName . ' ' . $lastName, 'email' => $customer->getEmail(), 'emailsender' => $emailsender);
				if ($post['account']['user_status'] == Magebuzz_Privatesale_Model_User::STATUS_CUSTOMER_APPROVED && $user->getIsSendmail() != Magebuzz_Privatesale_Model_User::STATUS_CUSTOMER_APPROVED) {
					$templateId = Mage::getStoreConfig(self::XML_PATH_CUSTOMER_APPROVAL_EMAIL_TEMPLATE);
					$user->setIsSendmail(Magebuzz_Privatesale_Model_User::STATUS_CUSTOMER_APPROVED);
					Mage::helper('privatesale')->sendMail($templateId, $customer->getEmail(), $dataInfo);
				} elseif ($post['account']['user_status'] == Magebuzz_Privatesale_Model_User::STATUS_CUSTOMER_REJECT && $user->getIsSendmail() != Magebuzz_Privatesale_Model_User::STATUS_CUSTOMER_REJECT) {
					$templateId = Mage::getStoreConfig(self::XML_PATH_CUSTOMER_REJECT_EMAIL_TEMPLATE);
					$user->setIsSendmail(Magebuzz_Privatesale_Model_User::STATUS_CUSTOMER_REJECT);
					Mage::helper('privatesale')->sendMail($templateId, $customer->getEmail(), $dataInfo);
				} else {
					$user->setIsSendmail(Magebuzz_Privatesale_Model_User::STATUS_CUSTOMER_PENDING);
				}
				$user->save();
			}
		}

		public function customerDeleteBeforeInBackend($observer)
		{
			$allowPendingCustomer = (int)Mage::getStoreConfig('privatesalesession/privatesale_options/allow_pending_customer');
			if ($allowPendingCustomer == 1) {
				$event = $observer->getEvent();
				$customer = $event->getCustomer();
				$user = Mage::getModel('privatesale/user')->getCollection()->addFieldToFilter('user_id', $customer->getEntityId());
				if (count($user->getData()) > 0) {
					foreach ($user as $u) {
						$u->delete();
					}
				}
			}
		}

		public function processFrontFinalPrice($observer)
		{
			$product = $observer->getEvent()->getProduct();
			$rulePrivate = Mage::helper('privatesale')->getHappeningEvents();
			$pId = $product->getId();

			foreach ($rulePrivate as $event) {
				$modelRule = Mage::getModel('privatesale/catalog_rule')->load($event->getEventId());
				$productIds = $modelRule->getMatchingProductIds();

				if (in_array($pId, $productIds)) {
					$this->setFinalPriceForProduct($product);
					return $this;
				}
			}


		}

		public function setFinalPriceForProduct($product)
		{
			$rulePrivate = Mage::helper('privatesale')->getHappeningEvents();
			$pId = $product->getId();
			if (count($rulePrivate->getData()) > 0) {
				$productPrice = $product->getData('final_price');
				foreach ($rulePrivate as $event) {
					$modelRule = Mage::getModel('privatesale/catalog_rule')->load($event->getEventId());
					$productIds = $modelRule->getMatchingProductIds();

					if (in_array($pId, $productIds)) {
						$finalPriceAfterDiscount = $product->getData('final_price');
						$discount = (int)$event->getDiscountAmount();
						if ($discount > 0) {
							$discount = ($discount / 100);
							$finalPriceAfterDiscount = $product->getData('final_price') * (1 - $discount);
						}
						$productPrice = min($productPrice, $finalPriceAfterDiscount);
					}
				}
				$product->setFinalPrice($productPrice);
			}
			return $this;
		}

		public function prepareCatalogProductPrices(Varien_Event_Observer $observer)
		{

			$collection = $observer->getEvent()->getCollection();
			$rulePrivate = Mage::helper('privatesale')->getHappeningEvents();


			foreach ($collection->getItems() as $product) {
				$pId = $product->getData('entity_id');
				$productPrice = $product->getData('final_price');
				foreach ($rulePrivate as $event) {
					$modelRule = Mage::getModel('privatesale/catalog_rule')->load($event->getEventId());
					$productIds = $modelRule->getMatchingProductIds();
					if (in_array($pId, $productIds)) {
						$finalPriceAfterDiscount = $product->getData('final_price');
						$discount = (int)$event->getDiscountAmount();
						if ($discount > 0) {
							$discount = ($discount / 100);
							$finalPriceAfterDiscount = $product->getData('final_price') * (1 - $discount);
						}
						$productPrice = min($product->getData('final_price'), $finalPriceAfterDiscount);
					}
				}
				$product->setFinalPrice($productPrice);
			}
			return;
		}
	}
