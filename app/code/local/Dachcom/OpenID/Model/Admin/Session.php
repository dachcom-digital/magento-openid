<?php
require_once (realpath(dirname(__FILE__))) . '/../../vendor/LightOpenID.php';

/**
 * Auth session model
 * @category    Dachcom
 * @package     Dachcom_OpenID
 */
class Dachcom_OpenID_Model_Admin_Session extends Mage_Admin_Model_Session {
    /**
     * Try to login user in admin
     * Evaluates additional request param "openid_identifier"
     *
     * @param  string $username
     * @param  string $password
     * @param  Mage_Core_Controller_Request_Http $request
     *
     * @return Mage_Admin_Model_User|null
     */
    public function login($username, $password, $request = NULL) {
        if ($request === null) {
            return parent::login($username, $password, $request);
        }

        if ($request->isPost()) {
            $login = $request->getPost('login');
            if (!empty($login['openid_identifier'])) {
                try {
                    $openid = new LightOpenID($this->getCurrentUrl($request));
                    $openid->identity = $login['openid_identifier'];

                    header('Location: ' . $openid->authUrl());
                    exit();
                } catch (Exception $e) {
                    Mage::getSingleton('adminhtml/session')
                        ->addError($e->getMessage());
                }

                return;
            }
        }
        if ($request->isGet()) {
            $login = $request->getParams();
            if (!empty($login['openid_mode'])) {
                try {
                    $openid = new LightOpenID($this->getCurrentUrl($request));

                    if ($openid->mode) {
                        if ($openid->validate()) {
                            try {
                                /* @var $user Dachcom_OpenID_Model_Admin_User */
                                $user = Mage::getModel('Dachcom_OpenID/admin_user');
                                $user->login($openid->identity, $password);
                                if ($user->getId()) {
                                    $this->renewSession();

                                    if (Mage::getSingleton('adminhtml/url')
                                        ->useSecretKey()
                                    ) {
                                        Mage::getSingleton('adminhtml/url')
                                            ->renewSecretUrls();
                                    }
                                    $this->setIsFirstPageAfterLogin(TRUE);
                                    $this->setUser($user);
                                    $this->setAcl(Mage::getResourceModel('admin/acl')
                                        ->loadAcl());

                                    $alternativeUrl = $this->_getRequestUri($request);
                                    $redirectUrl = $this->_urlPolicy->getRedirectUrl($user, $request, $alternativeUrl);
                                    if ($redirectUrl) {
                                        Mage::dispatchEvent('admin_session_user_login_success', array('user' => $user));
                                        $this->_response->clearHeaders()
                                            ->setRedirect($redirectUrl)
                                            ->sendHeadersAndExit();
                                    }

                                    return $user;
                                }
                                else {
                                    Mage::throwException(Mage::helper('adminhtml')
                                        ->__('Invalid User Name or Password.'));
                                }
                            } catch (Mage_Core_Exception $e) {
                                Mage::dispatchEvent('admin_session_user_login_failed', array(
                                    'user_name' => $username,
                                    'exception' => $e
                                ));
                                if ($request && !$request->getParam('messageSent')) {
                                    Mage::getSingleton('adminhtml/session')
                                        ->addError($e->getMessage());
                                    $request->setParam('messageSent', TRUE);
                                }
                            }
                            return;
                        }

                        Mage::getSingleton('adminhtml/session')
                            ->addError('OpenID login failed.');
                    }
                } catch (Exception $e) {
                    Mage::getSingleton('adminhtml/session')
                        ->addError($e->getMessage());
                }

                return;
            }
        }
        return parent::login($username, $password, $request);
    }

    private function getCurrentUrl($request) {
        $port = $request->getServer('SERVER_PORT');
        if (empty($port) || $port === 80 || $port === 443) {
            $port = '';
        }
        return $request->getScheme() . '://' . $request->getHttpHost() . ($port ? ':' . $port : '');
    }
}
