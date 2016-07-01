<?php
/*
* @copyright Copyright (c) 2016 www.magebuzz.com
*/

class Magebuzz_Privatesale_Adminhtml_Privatesale_InviteController extends Mage_Adminhtml_Controller_action {
  public function indexAction() {
    $this->loadLayout();
    $this->getLayout()->getBlock('head')->setTitle('Invite Customer');
    $this->renderLayout();
  }

  public function formAction() {
    $this->loadLayout();
    //$this->getLayout()->getBlock('head')->setTitle('Invite Customer');
    $this->_initLayoutMessages('admin/session');
    $this->renderLayout();
  }

  private function genRandomString() {
    $length = 20;
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $string = '';	
		$maxRandValue = strlen($characters) - 1;
    for ($p = 0; $p < $length; $p++) {
      $string .= $characters[mt_rand(0, $maxRandValue)];
    }
    return $string;
  }

  public function inviteAction() {
    $data = $this->getRequest()->getPost('email');
    $message = $this->getRequest()->getPost('message');
    $validator = new Zend_Validate_EmailAddress();
    $model = Mage::getModel('privatesale/invite');
    $adminInfo = Mage::getSingleton('admin/session')->getUser();
    foreach ($data as $email) {
      if ($validator->isValid($email)) {
        $sign_up_code = $this->genRandomString();
        $emailvars = array();
        $emailvars['referer_email'] = $adminInfo->getEmail();
        $emailvars['referer_name'] = $adminInfo->getName();
        $emailvars['invited_customer_email'] = $email;
        $emailvars['invited_customer_sign_up_code'] = $sign_up_code;
        $emailvars['address'] = Mage::getBaseUrl() . 'customer/account/create';
        $emailvars['message'] = $message;
        try {
          Mage::helper('privatesale')->sendInviteEmail($email, Mage::helper('customer')->getCustomer()->getName(), $emailvars);
        } catch (Exception $e) {
          Mage::getSingleton('admin/session')->addError($e->getMessage());
        }
        $invite = array('referer_id' => $adminInfo->getId(), 'referer_email' => $adminInfo->getEmail(), 'invited_customer_id' => 0, 'invited_customer_email' => $email, 'invited_customer_sign_up_code' => $sign_up_code, 'is_admin' => 1);
        $model->setData($invite)->save();
        Mage::getSingleton('admin/session')->addSuccess('Your invitation to email ' . $email . ' has been sent!');
      } else {
        Mage::getSingleton('admin/session')->addError('An error occurred while try to send email to ' . $email . ' please try again later!');
      }
    }
    $this->_redirect('*/*/form');
  }

  public function checkAction() {
    $data = $this->getRequest()->getPost();
    $validator = new Zend_Validate_EmailAddress();
    if ($validator->isValid($data['email'])) {
      $collection = Mage::getModel('customer/customer')->getCollection()->addFieldToFilter('email', $data['email'])->getData();
      if (empty($collection)) echo 0; else echo 1;
    } else {
      echo -1;
    }
  }
	
	protected function _isAllowed() {
		return Mage::getSingleton('admin/session')->isAllowed('privatesale');
	}
}