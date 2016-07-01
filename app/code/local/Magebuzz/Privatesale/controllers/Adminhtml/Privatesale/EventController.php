<?php
/*
* Copyright (c) 2016 www.magebuzz.com
*/

class Magebuzz_Privatesale_Adminhtml_Privatesale_EventController extends Mage_Adminhtml_Controller_Action {
  protected function _initAction() {
    $this->loadLayout()->_setActiveMenu('privatesale/items')->_addBreadcrumb(Mage::helper('adminhtml')->__('Items Manager'), Mage::helper('adminhtml')->__('Item Manager'));

    return $this;
  }

  public function indexAction() {
    $this->_initAction()->renderLayout();
  }

  public function editAction() {
    $id = $this->getRequest()->getParam('id');
    $model = Mage::getModel('privatesale/event')->load($id);
    if ($model->getId() || $id == 0) {
      $data = Mage::getSingleton('adminhtml/session')->getFormData(TRUE);
      if (!empty($data)) {
        $model->setData($data);
      }
      Mage::register('privatesale_event', $model);

      $this->loadLayout();
      $this->_setActiveMenu('privatesale/items');

      $this->_addBreadcrumb(Mage::helper('adminhtml')->__('Item Manager'), Mage::helper('adminhtml')->__('Item Manager'));
      $this->_addBreadcrumb(Mage::helper('adminhtml')->__('Item News'), Mage::helper('adminhtml')->__('Item News'));

      $this->getLayout()->getBlock('head')->setCanLoadExtJs(TRUE);

      $this->_addContent($this->getLayout()->createBlock('privatesale/adminhtml_event_edit'))->_addLeft($this->getLayout()->createBlock('privatesale/adminhtml_event_edit_tabs'));

      $this->renderLayout();
    } else {
      Mage::getSingleton('adminhtml/session')->addError(Mage::helper('privatesale')->__('Item does not exist'));
      $this->_redirect('*/*/');
    }
  }

  public function newAction() {
    $this->_forward('edit');
  }

  public function saveAction() {
    if ($data = $this->getRequest()->getPost()) {
      if (isset($_FILES['image']['name']) && $_FILES['image']['name'] != '') {
        try {
          $uploader = new Varien_File_Uploader('image');
          $uploader->setAllowedExtensions(array('jpg', 'jpeg', 'gif', 'png'));
          $uploader->setAllowRenameFiles(FALSE);
          $uploader->setFilesDispersion(FALSE);
          $path = Mage::getBaseDir('media') . DS . 'privatesales' . DS . 'events' . DS;
          $uploader->save($path, $_FILES['image']['name']);
        } catch (Exception $e) {
          Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__('Images Upload Error'));
          if (!isset($data['_continue'])) $this->_redirect('*/*/'); else $this->_redirect('*/*/edit', array('id' => $data['id'],));
        }
      }

      if (isset($data['image']['delete']) && $data['image']['delete'] == '1') {
        $data['image'] = '';
      }

      if (isset($_FILES['image']['name'])) {
        $data['image'] = $_FILES['image']['name'];
      }

      if (isset($data['image']) && $_FILES['image']['name'] == '') {
        $eventId = $this->getRequest()->getParam('id');
        $imageEvent = Mage::helper('privatesale')->loadCurrentImage($eventId);
        $data['image'] = $imageEvent;
      }

      $data['status'] = $this->getStatus($data['start_date'], $data['end_date']);
      $data['conditions'] = $data['rule']['conditions'];
      unset($data['rule']);
      $model = Mage::getModel('privatesale/catalog_rule');

      $model->setData($data);
      $model->loadPost($data);
      $model->setId($this->getRequest()->getParam('id'));

      try {
        $model->save();
        //rewrite url Events
        $is_edit_title = FALSE;
        if ($model->getId() && $model->getTitle() != $data['title']) {
          $is_edit_title = TRUE;
        }
        $url_key = Mage::helper('privatesale')->generateUrlKey($model->getTitle());
        $request_path = 'saleevents/' . $url_key;

        $rewriteModel = Mage::getModel('core/url_rewrite');
        $id_path = 'privatesale/index/view/id/' . $model->getId();
        $rewriteModel->loadByIdPath($id_path);
        $store_id = Mage::app()->getStore()->getId();
        if ($rewriteModel->getId()) {
          //existing rewrite URL
          if ($is_edit_title) {
            $request_path .= '.html';
            $rewriteModel->setData('request_path', $request_path);
            $rewriteModel->setData('target_path', 'privatesale/index/view/id/' . $model->getId());
            $rewriteModel->setData('store_id', $store_id);
            $rewriteModel->setData('is_system', 0);
            $rewriteModel->save();
            // save url key for events
            $eventModule = Mage::getModel('privatesale/event')->load($model->getId());
            $eventModule->setUrlKey($url_key);
            $eventModule->save();
            $model->setUrlKey($url_key);
          }
        } else {
          // create new rewrite URL
          // avoid duplicate request path
          $rewriteModel = Mage::getModel('core/url_rewrite')->loadByRequestPath($request_path . '.html');

          if ($rewriteModel->getId()) {
            $url_key = $url_key . '-' . $model->getId();
            $request_path = $request_path . '-' . $model->getId();
          }
          $request_path .= '.html';
          $urlRewrite = Mage::getModel('core/url_rewrite');
          $urlRewrite->setData('id_path', $id_path);
          $urlRewrite->setData('request_path', $request_path);
          $urlRewrite->setData('target_path', 'privatesale/index/view/id/' . $model->getId());
          $urlRewrite->setData('store_id', $store_id);
          $urlRewrite->setData('is_system', 0);
          $urlRewrite->save();

          // save url key for events
          $eventModule = Mage::getModel('privatesale/event')->load($model->getId());
          $eventModule->setUrlKey($url_key);
          $eventModule->save();
          $model->setUrlKey($url_key);
        }
        //end rewrite

        $idEvent = $this->getRequest()->getParam('id');
        if ($idEvent == null || $idEvent <= 0) {
          Mage::helper('privatesale')->sendEmailNotificationToCustomer($model);
        }

        Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('privatesale')->__('Item was successfully saved'));
        Mage::getSingleton('adminhtml/session')->setFormData(FALSE);

        if ($this->getRequest()->getParam('back')) {
          $this->_redirect('*/*/edit', array('id' => $model->getId()));
          return;
        }
        $this->_redirect('*/*/');
        return;
      } catch (Exception $e) {
        Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        Mage::getSingleton('adminhtml/session')->setFormData($data);
        $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
        return;
      }
    }
    Mage::getSingleton('adminhtml/session')->addError(Mage::helper('privatesale')->__('Unable to find item to save'));
    $this->_redirect('*/*/');
  }

  private function getStatus($start, $end) {
    $startTime = Mage::app()->getLocale()->date($start, null, null, FALSE);
    $endTime = Mage::app()->getLocale()->date($end, null, null, FALSE);
    $currentSystemTime = Mage::app()->getLocale()->date(Mage::getModel('core/date')->date(), Varien_Date::DATETIME_INTERNAL_FORMAT, null, FALSE);
    $isStarted = $currentSystemTime->compare($startTime);
    $isExpired = $currentSystemTime->compare($endTime);
    if ($isExpired > 0) return 3; elseif ($isStarted >= 0) return 2;
    else return 1;
  }

  public function deleteAction() {
    if ($this->getRequest()->getParam('id') > 0) {
      try {
        $model = Mage::getModel('privatesale/event');

        $model->setId($this->getRequest()->getParam('id'))->delete();

        Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('adminhtml')->__('Item was successfully deleted'));
        $this->_redirect('*/*/');
      } catch (Exception $e) {
        Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
      }
    }
    $this->_redirect('*/*/');
  }

  public function massDeleteAction() {
    $privatesaleIds = $this->getRequest()->getParam('privatesale');
    if (!is_array($privatesaleIds)) {
      Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__('Please select item(s)'));
    } else {
      try {
        foreach ($privatesaleIds as $privatesaleId) {
          $privatesale = Mage::getModel('privatesale/event')->load($privatesaleId);
          $privatesale->delete();
        }
        Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('adminhtml')->__('Total of %d record(s) were successfully deleted', count($privatesaleIds)));
      } catch (Exception $e) {
        Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
      }
    }
    $this->_redirect('*/*/index');
  }

  public function massStatusAction() {
    $privatesaleIds = $this->getRequest()->getParam('privatesale');
    if (!is_array($privatesaleIds)) {
      Mage::getSingleton('adminhtml/session')->addError($this->__('Please select item(s)'));
    } else {
      try {
        foreach ($privatesaleIds as $privatesaleId) {
          $privatesale = Mage::getSingleton('privatesale/event')->load($privatesaleId)->setStatus($this->getRequest()->getParam('status'))->setIsMassupdate(TRUE)->save();
        }
        $this->_getSession()->addSuccess($this->__('Total of %d record(s) were successfully updated', count($privatesaleIds)));
      } catch (Exception $e) {
        $this->_getSession()->addError($e->getMessage());
      }
    }
    $this->_redirect('*/*/index');
  }

  public function exportCsvAction() {
    $fileName = 'privatesale.csv';
    $content = $this->getLayout()->createBlock('privatesale/adminhtml_privatesale_grid')->getCsv();

    $this->_sendUploadResponse($fileName, $content);
  }

  public function exportXmlAction() {
    $fileName = 'privatesale.xml';
    $content = $this->getLayout()->createBlock('privatesale/adminhtml_privatesale_grid')->getXml();

    $this->_sendUploadResponse($fileName, $content);
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

  public function newConditionHtmlAction() {
    $id = $this->getRequest()->getParam('id');
    $typeArr = explode('|', str_replace('-', '/', $this->getRequest()->getParam('type')));
    $type = $typeArr[0];

    $model = Mage::getModel($type)->setId($id)->setType($type)->setRule(Mage::getModel('privatesale/catalog_rule'))->setPrefix('conditions');
    if (!empty($typeArr[1])) {
      $model->setAttribute($typeArr[1]);
    }

    if ($model instanceof Mage_Rule_Model_Condition_Abstract) {
      $model->setJsFormObject($this->getRequest()->getParam('form'));
      $html = $model->asHtmlRecursive();
    } else {
      $html = '';
    }
    $this->getResponse()->setBody($html);
  }

  public function chooserAction() {
    switch ($this->getRequest()->getParam('attribute')) {
      case 'sku':
        $type = 'adminhtml/promo_widget_chooser_sku';
        break;

      case 'categories':
        $type = 'adminhtml/promo_widget_chooser_categories';
        break;
    }
    if (!empty($type)) {
      $block = $this->getLayout()->createBlock($type);
      if ($block) {
        $this->getResponse()->setBody($block->toHtml());
      }
    }
  }

	protected function _isAllowed() {
		return Mage::getSingleton('admin/session')->isAllowed('privatesale/events');
	}
}