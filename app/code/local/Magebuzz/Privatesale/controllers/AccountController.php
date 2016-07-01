<?php
/*
* @copyright   Copyright (c) 2016 www.magebuzz.com
*/
require_once 'Mage/Customer/controllers/AccountController.php';

class Magebuzz_Privatesale_AccountController extends Mage_Customer_AccountController {
  public function createPostAction() {
    $requireSignUpCode = (int)Mage::getStoreConfig('privatesalesession/privatesale_options/open_sign_up');
    $isRedirectLanding = (int)Mage::getStoreConfig('privatesalesession/privatesale_options/redirect_to_landing');
    $allowPendingCustomer = (int)Mage::getStoreConfig('privatesalesession/privatesale_options/allow_pending_customer');
    $data = $this->getRequest()->getPost();
    Mage::getSingleton('customer/session')->setCustomerRegisterData($data);
    if ($requireSignUpCode == 1 || $allowPendingCustomer == 1) {

      if ($requireSignUpCode == 1) {
        $enteredCode = $data['sign_up_code'];
        $model = Mage::getModel('privatesale/invite');
        $collection = Mage::getModel('privatesale/invite')->getCollection()->addFieldToFilter('invited_customer_email', $data['email'])->getData();
        $allow = FALSE;
        foreach ($collection as $item) {
          if ($enteredCode == $item['invited_customer_sign_up_code']) {
            $allow = TRUE;
            break;
          }
        }
        if (!$allow) {
          Mage::getSingleton('customer/session')->addError(Mage::helper('privatesale')->__('The invitation code you enterred is not correct. Please try again.'));
          $this->redirectUrl(Mage::getBaseUrl() . 'customer/account/create', 'register');
          return;
        }
      }
      $session = $this->_getSession();
      if ($session->isLoggedIn()) {
        $this->_redirect('*/*/');
        return;
      }
      $session->setEscapeMessages(TRUE); // prevent XSS injection in user input
      if ($this->getRequest()->isPost()) {
        $errors = array();

        if (!$customer = Mage::registry('current_customer')) {
          $customer = Mage::getModel('customer/customer')->setId(null);
        }

        /* @var $customerForm Mage_Customer_Model_Form */
        $customerForm = Mage::getModel('customer/form');
        $customerForm->setFormCode('customer_account_create')->setEntity($customer);

        $customerData = $customerForm->extractData($this->getRequest());

        if ($this->getRequest()->getParam('is_subscribed', FALSE)) {
          $customer->setIsSubscribed(1);
        }

        /**
         * Initialize customer group id
         */
        $customer->getGroupId();

        if ($this->getRequest()->getPost('create_address')) {
          /* @var $address Mage_Customer_Model_Address */
          $address = Mage::getModel('customer/address');
          /* @var $addressForm Mage_Customer_Model_Form */
          $addressForm = Mage::getModel('customer/form');
          $addressForm->setFormCode('customer_register_address')->setEntity($address);

          $addressData = $addressForm->extractData($this->getRequest(), 'address', FALSE);
          $addressErrors = $addressForm->validateData($addressData);
          if ($addressErrors === TRUE) {
            $address->setId(null)->setIsDefaultBilling($this->getRequest()->getParam('default_billing', FALSE))->setIsDefaultShipping($this->getRequest()->getParam('default_shipping', FALSE));
            $addressForm->compactData($addressData);
            $customer->addAddress($address);

            $addressErrors = $address->validate();
            if (is_array($addressErrors)) {
              $errors = array_merge($errors, $addressErrors);
            }
          } else {
            $errors = array_merge($errors, $addressErrors);
          }
        }

        try {
          $customerErrors = $customerForm->validateData($customerData);
          if ($customerErrors !== TRUE) {
            $errors = array_merge($customerErrors, $errors);
          } else {
            $customerForm->compactData($customerData);
						if (Mage::getVersion() >= '1.9.1.0') {
							$customer->setPassword($this->getRequest()->getPost('password'));
							$customer->setPasswordConfirmation($this->getRequest()->getPost('confirmation'));
						}
						else {
							$customer->setPassword($this->getRequest()->getPost('password'));
							$customer->setConfirmation($this->getRequest()->getPost('confirmation'));
						}
            $customerErrors = $customer->validate();
            if (is_array($customerErrors)) {
              $errors = array_merge($customerErrors, $errors);
            }
          }

          $validationResult = count($errors) == 0;

          if (TRUE === $validationResult) {
            $customer->save();

            if ($allowPendingCustomer == 1) {
              $user = Mage::getModel('privatesale/user')->loadByUserId($customer->getId());
              $user->setUserStatus(Magebuzz_Privatesale_Model_User::STATUS_CUSTOMER_PENDING);
              $user->save();
            }
            Mage::dispatchEvent('customer_register_success', array('account_controller' => $this, 'customer' => $customer));
            Mage::getSingleton('customer/session')->setCustomerRegisterData('');
            if ($customer->isConfirmationRequired()) {
              $customer->sendNewAccountEmail('confirmation', $session->getBeforeAuthUrl(), Mage::app()->getStore()->getId());
              $session->addSuccess(Mage::helper('privatesale')->__('Account confirmation is required. Please check your email for the confirmation link. To resend the confirmation email please <a href="%s">click here</a>.', Mage::helper('customer')->getEmailConfirmationUrl($customer->getEmail())));
              $this->redirectUrl(Mage::getUrl('*/*/index', array('_secure' => TRUE)));
              return;
            } else {
              $statusUserLogin = $this->checkUser($customer->getId(), $flag = 'isCreate');
              if (!$statusUserLogin) {
                $this->redirectUrl(Mage::getUrl('*/*/index', array('_secure' => TRUE)));
                return;
              }
              $session->setCustomerAsLoggedIn($customer);
              $id = $customer->getId();
              foreach ($collection as $item) {
                $item['invited_customer_id'] = $id;
                $model->setData($item)->setId($item['invite_id'])->save();
              }
              $url = $this->_welcomeCustomer($customer);
              $this->_redirectSuccess($url);
              return;
            }
          } else {
            $session->setCustomerFormData($this->getRequest()->getPost());
            if (is_array($errors)) {
              foreach ($errors as $errorMessage) {
                $session->addError($errorMessage);
              }
            } else {
              $session->addError(Mage::helper('privatesale')->__('Invalid customer data'));
            }
          }
        } catch (Mage_Core_Exception $e) {
          $session->setCustomerFormData($this->getRequest()->getPost());
          if ($e->getCode() === Mage_Customer_Model_Customer::EXCEPTION_EMAIL_EXISTS) {
            $url = Mage::getUrl('customer/account/forgotpassword');
            $message = Mage::helper('privatesale')->__('There is already an account with this email address. If you are sure that it is your email address, <a href="%s">click here</a> to get your password and access your account.', $url);
            $session->setEscapeMessages(FALSE);
          } else {
            $message = $e->getMessage();
          }
          $session->addError($message);
        } catch (Exception $e) {
          $session->setCustomerFormData($this->getRequest()->getPost())->addException($e, Mage::helper('privatesale')->__('Cannot save the customer.'));
        }
      }
      $this->redirectUrl(Mage::getUrl('*/*/create', array('_secure' => TRUE)));
    } else parent::createPostAction();
  }

  public function redirectUrl($url, $param = null) {
    $requireSignUpCode = Mage::getStoreConfig('privatesalesession/privatesale_options/force_user_to_login');
    $isRedirectLanding = (int)Mage::getStoreConfig('privatesalesession/privatesale_options/redirect_to_landing');
    $identifier = Mage::getStoreConfig('privatesalesession/privatesale_options/cms_landing_page');
    if ($requireSignUpCode) {
      if ($isRedirectLanding == 1) {
        $session = $this->_getSession();
        if ($session->isLoggedIn()) {
          $this->_redirect();
          return;
        } else {
          $urlRedirect = Mage::getUrl($identifier);
          if ($param != null) {
            $urlRedirect .= '?';
            $urlRedirect .= $param;
          }
          $this->_redirectUrl($urlRedirect);
          return;
        }
      }
    }
    Mage::app()->getFrontController()->getResponse()->setRedirect($url);
  }

  public function forgotPasswordPostAction() {
    $email = (string)$this->getRequest()->getPost('email');
    if ($email) {
      if (!Zend_Validate::is($email, 'EmailAddress')) {
        $this->_getSession()->setForgottenEmail($email);
        $this->_getSession()->addError($this->__('Invalid email address.'));
        //$this->_redirect('*/*/forgotpassword');
        $this->redirectUrl(Mage::getUrl('*/*/forgotpassword'));
        return;
      }

      /** @var $customer Mage_Customer_Model_Customer */
      $customer = $this->_getModel('customer/customer')->setWebsiteId(Mage::app()->getStore()->getWebsiteId())->loadByEmail($email);

      if ($customer->getId()) {
        try {
          $newResetPasswordLinkToken = $this->_getHelper('customer')->generateResetPasswordLinkToken();
          $customer->changeResetPasswordLinkToken($newResetPasswordLinkToken);
          $customer->sendPasswordResetConfirmationEmail();
        } catch (Exception $exception) {
          $this->_getSession()->addError($exception->getMessage());
          $this->redirectUrl(Mage::getUrl('*/*/forgotpassword'));
          return;
        }
      }
      $this->_getSession()->addSuccess($this->_getHelper('customer')->__('If there is an account associated with %s you will receive an email with a link to reset your password.', $this->_getHelper('customer')->escapeHtml($email)));
      $this->redirectUrl(Mage::getUrl('*/*/'));
      return;
    } else {
      $this->_getSession()->addError($this->__('Please enter your email.'));
      $this->redirectUrl(Mage::getUrl('*/*/forgotpassword'));
      return;
    }
  }

  public function loginPostAction() {
    $allowPendingCustomer = (int)Mage::getStoreConfig('privatesalesession/privatesale_options/allow_pending_customer');

    if ($this->_getSession()->isLoggedIn()) {
      $this->_redirect('*/*/');
      return;
    }
    $session = $this->_getSession();

    if ($allowPendingCustomer == 1) {
      if ($this->getRequest()->isPost()) {
        $login = $this->getRequest()->getPost('login');
        if (!empty($login['username']) && !empty($login['password'])) {
          $customerCheck = Mage::getModel('customer/customer')->setWebsiteId(Mage::app()->getWebsite()->getId())->loadByEmail($login['username']);
          if (!$customerCheck->getId()) {
            $session->addError($this->__('Invalid login or password'));
            $this->_loginPostRedirect();
            return;
          } else {
            $statusUserLogin = $this->checkUser($customerCheck->getId(), $flag = 'isLogin');
            if (!$statusUserLogin) {
              $this->_loginPostRedirect();
              return;
            }
          }
          try {
            $session->login($login['username'], $login['password']);
            if ($session->getCustomer()->getIsJustConfirmed()) {
              $this->_welcomeCustomer($session->getCustomer(), TRUE);
            }
          } catch (Mage_Core_Exception $e) {
            switch ($e->getCode()) {
              case Mage_Customer_Model_Customer::EXCEPTION_EMAIL_NOT_CONFIRMED:
                $value = $this->_getHelper('customer')->getEmailConfirmationUrl($login['username']);
                $message = $this->_getHelper('customer')->__('This account is not confirmed. <a href="%s">Click here</a> to resend confirmation email.', $value);
                break;
              case Mage_Customer_Model_Customer::EXCEPTION_INVALID_EMAIL_OR_PASSWORD:
                $message = $e->getMessage();
                break;
              default:
                $message = $e->getMessage();
            }
            $session->addError($message);
            $session->setUsername($login['username']);
          } catch (Exception $e) {
            // Mage::logException($e); // PA DSS violation: this exception log can disclose customer password
          }
        } else {
          $session->addError($this->__('Login and password are required.'));
        }
      }
      $this->_loginPostRedirect();
    } else {
      parent::loginPostAction();
    }
  }

  public function checkUser($userId, $flag) {
    $session = $this->_getSession();
    $privateUser = Mage::getModel('privatesale/user')->loadByUserId($userId);
    if ($privateUser->getUserStatus() == Magebuzz_Privatesale_Model_User::STATUS_CUSTOMER_REJECT) {
      $session->addError($this->__('Your account was Rejected and you cannot log in our store.'));
      return FALSE;
    } else if ($privateUser->getUserStatus() == Magebuzz_Privatesale_Model_User::STATUS_CUSTOMER_PENDING) {
      if ($flag == 'isCreate') {
        $session->addNotice($this->__('Thank you for your registration. We will review and grant your access as soon as possible.'));
      } else if ($flag == 'isLogin') {
        $session->addNotice($this->__('Your account is not activated and you cannot log in our store.'));
      }
      return FALSE;
    } else {
      return TRUE;
    }
  }
}