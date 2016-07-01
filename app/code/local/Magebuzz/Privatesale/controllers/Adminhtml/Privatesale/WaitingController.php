<?php
/*
* @copyright Copyright (c) 2016 www.magebuzz.com
*/

class Magebuzz_Privatesale_Adminhtml_Privatesale_WaitingController extends Mage_Adminhtml_Controller_action {
  const XML_PATH_ADMIN_SEND_CODE_EMAIL_TEMPLATE = 'privatesalesession/email_options/admin_send_invite_code_template';

  protected function _initAction() {
    $this->loadLayout()->_setActiveMenu('privatesale/items')->_addBreadcrumb(Mage::helper('adminhtml')->__('Items Manager'), Mage::helper('adminhtml')->__('Item Manager'));

    return $this;
  }

  public function indexAction() {
    $this->_initAction()->renderLayout();
  }

  public function gridAction() {
    $this->loadLayout();
    $this->getResponse()->setBody($this->getLayout()->createBlock('privatesale/adminhtml_waiting_gridwaiting')->toHtml());
  }

  public function massStatusAction() {
    $waiting_ids = $this->getRequest()->getParam('waiting_id');
    $status = $this->getRequest()->getParam('status');
    $adminInfo = Mage::getSingleton('admin/session')->getUser();
    $emailTemplate = Mage::getStoreConfig(self::XML_PATH_ADMIN_SEND_CODE_EMAIL_TEMPLATE);
    if (!is_array($waiting_ids)) {
      Mage::getSingleton('adminhtml/session')->addError($this->__('Please select item(s)'));
    } else {
      try {
        foreach ($waiting_ids as $waiting_id) {
          $model = Mage::getSingleton('privatesale/waiting')->load($waiting_id)->setStatus($status)->save();
          $modelInvite = Mage::getSingleton('privatesale/invite');
          if ($status == Magebuzz_Privatesale_Model_Statuswaiting::STATUS_APPROVED) {
            $sign_up_code = $this->genRandomString();
            $emailvars = array();
            $emailvars['referer_email'] = $adminInfo->getEmail();
            $emailvars['invited_customer_email'] = $model->getEmailWaiting();
            $emailvars['invited_customer_name'] = $model->getNameWaiting();
            $emailvars['invited_customer_sign_up_code'] = $sign_up_code;
            $emailvars['address'] = Mage::getBaseUrl() . 'customer/account/create';
            $invite = array('referer_id' => $adminInfo->getId(), 'referer_email' => $adminInfo->getEmail(), 'invited_customer_id' => 0, 'invited_customer_email' => $model->getEmailWaiting(), 'invited_customer_sign_up_code' => $sign_up_code, 'is_admin' => 1);
            $modelInvite->setData($invite);
            try {
              $modelInvite->save();
              Mage::helper('privatesale')->sendMail($emailTemplate, $model->getEmailWaiting(), $emailvars);
            } catch (Exception $e) {
              $this->_getSession()->addError($e->getMessage());
            }
          } elseif ($status == Magebuzz_Privatesale_Model_Statuswaiting::STATUS_REJECT) {
            $templateInvitation = Mage::getStoreConfig('privatesalesession/email_options/invitation_reject_email_template');
            $emailSender = Mage::getStoreConfig('privatesalesession/email_options/email_sender');
            $translate = Mage::getSingleton('core/translate');
            $translate->setTranslateInline(FALSE);
            try {
              $emailvars = array();
              $emailvars['referer_email'] = $adminInfo->getEmail();
              $emailvars['request_email'] = $model->getEmailWaiting();
              $mailTemplate = Mage::getModel('core/email_template');
              $mailTemplate->setDesignConfig(array('area' => 'frontend'))->setReplyTo($adminInfo->getEmail())->sendTransactional($templateInvitation, $emailSender, $model->getEmailWaiting(), null, array('data' => new Varien_Object($emailvars)));
              if (!$mailTemplate->getSentSuccess()) {
                throw new Exception();
              }
              $translate->setTranslateInline(TRUE);
            } catch (Exception $e) {
              $translate->setTranslateInline(TRUE);
            }
          }
        }
        $this->_getSession()->addSuccess($this->__('Total of %d record(s) were successfully updated', count($waiting_ids)));
      } catch (Exception $e) {
        $this->_getSession()->addError($e->getMessage());
      }
    }
    $this->_redirect('*/*/index');
  }

  public function sendcodeAction() {
    $waiting_id = $this->getRequest()->getParam('id');
    $status = Magebuzz_Privatesale_Model_Statuswaiting::STATUS_APPROVED;
    $adminInfo = Mage::getSingleton('admin/session')->getUser();
    $emailTemplate = Mage::getStoreConfig(self::XML_PATH_ADMIN_SEND_CODE_EMAIL_TEMPLATE);
    if ($waiting_id == null) {
      Mage::getSingleton('adminhtml/session')->addError($this->__('Please select item(s)'));
    } else {
      $model = Mage::getModel('privatesale/waiting')->load($waiting_id);
      try {
        $model->setStatus($status)->save();
        $modelInvite = Mage::getSingleton('privatesale/invite');
        $sign_up_code = $this->genRandomString();
        $emailvars = array();
        $emailvars['referer_email'] = $adminInfo->getEmail();
        $emailvars['invited_customer_email'] = $model->getEmailWaiting();
        $emailvars['invited_customer_name'] = $model->getNameWaiting();
        $emailvars['invited_customer_sign_up_code'] = $sign_up_code;
        $emailvars['address'] = Mage::getBaseUrl() . 'customer/account/create';
        Mage::helper('privatesale')->sendMail($emailTemplate, $model->getEmailWaiting(), $emailvars);
        $invite = array('referer_id' => $adminInfo->getId(), 'referer_email' => $adminInfo->getEmail(), 'invited_customer_id' => 0, 'invited_customer_email' => $model->getEmailWaiting(), 'invited_customer_sign_up_code' => $sign_up_code, 'is_admin' => 1,);
        $modelInvite->setData($invite);
        try {
          $modelInvite->save();
        } catch (Exception $e) {
          $this->_getSession()->addError($e->getMessage());
        }
        $this->_getSession()->addSuccess($this->__('The registration code was sent to customer successfully'));
      } catch (Exception $e) {
        $this->_getSession()->addError($e->getMessage());
      }
    }
    $this->_redirect('*/*/index');
  }

  public function massDeleteAction() {
    $waiting_ids = $this->getRequest()->getParam('waiting_id');
    if (!is_array($waiting_ids)) {
      Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__('Please select item(s)'));
    } else {
      try {
        foreach ($waiting_ids as $waiting_id) {
          $model = Mage::getModel('privatesale/waiting')->load($waiting_id);
          $model->delete();
        }
        Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('adminhtml')->__('Total of %d record(s) were successfully deleted', count($waiting_ids)));
      } catch (Exception $e) {
        Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
      }
    }
    $this->_redirect('*/*/index');
  }

  protected function _sendUploadResponse($fileName, $content, $contentType = 'application/octet-stream') {
    $response = $this->getResponse();
    $response->setHeader('HTTP/1.1 200 OK', '');
    $response->setHeader('Pragma', 'public', TRUE);
    $response->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0', TRUE);
    $response->setHeader('Content-Disposition', 'attachment; filename=' . $fileName);
    $response->setHeader('Last-Modified', date('r'));
    $response->setHeader('Accept-Ranges', 'bytes');
    $response->setHeader('Content-Length', strlen($content));
    $response->setHeader('Content-type', $contentType);
    $response->setBody($content);
    $response->sendResponse();
    die;
  }

  private function genRandomString() {
    $length = 20;
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $string = '';
		$maxRandomValue = strlen($characters) - 1;
    for ($p = 0; $p < $length; $p++) {
      $string .= $characters[mt_rand(0, $maxRandomValue)];
    }
    return $string;
  }

  public function clearAction() {
    $coreResource = Mage::getSingleton('core/resource');
    $resource = Mage::getModel('privatesale/waiting')->getCollection()->addFieldToFilter('status', array('in' => array(Magebuzz_Privatesale_Model_Statuswaiting::STATUS_APPROVED, Magebuzz_Privatesale_Model_Statuswaiting::STATUS_REJECT)));
    foreach ($resource as $wait) {
      $wait->delete();
    }
    Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('adminhtml')->__('System refreshed success.'));
    $this->_redirect('adminhtml/privatesale_waiting/index');
  }
	
	protected function _isAllowed() {
		return Mage::getSingleton('admin/session')->isAllowed('privatesale');
	}
}