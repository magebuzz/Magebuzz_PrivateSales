<?php
/*
* @copyright Copyright (c) 2016 www.magebuzz.com
*/

class Magebuzz_Privatesale_IndexController extends Mage_Core_Controller_Front_Action {
  public function indexAction() {
    $this->loadLayout();
    $this->getLayout()->getBlock('head')->setTitle(Mage::helper('privatesale')->__('Private Sale Events'));
    $this->renderLayout();
  }

  public function changeConfigAction() {
    $config = $this->getRequest()->getParams();
    $user = Mage::getModel('privatesale/user')->loadByUserId(Mage::getSingleton('customer/session')->getCustomerId());
    if ($config['receive_email_config']) {
      $user->setUserConfig(1);
      $user->save();
    } else {
      $user->setUserConfig('');
      $user->save();
    }
    $this->_redirect('privatesale/invite');
  }

  public function viewAction() {
    if ($event = $this->_initEvent()) {
      $this->loadLayout();

      $this->getLayout()->getBlock('head')->setTitle($event->getTitle() . ' - ' . Mage::helper('privatesale')->__('Sales Events'));
      $this->renderLayout();
    } elseif (!$this->getResponse()->isRedirect()) {
      $this->_forward('noRoute');
    }
  }

  protected function _initEvent() {
    $id = (int)$this->getRequest()->getParam('id', FALSE);
    if (!$id) {
      return FALSE;
    }

    $event = Mage::getModel('privatesale/event')->load($id);
    Mage::register('current_event', $event);
    return $event;
  }

}