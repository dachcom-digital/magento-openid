<?php

/**
 * Admin observer model
 * Handles the OpenID return request
 * @category Dachcom
 * @package  Dachcom_OpenID
 */
class Dachcom_OpenID_Model_Admin_Observer extends Mage_Admin_Model_Observer {

    public function actionPreDispatchAdmin($event) {
        /* @var $session Dachcom_OpenID_Model_Admin_Session */
        $session = Mage::getSingleton('Dachcom_OpenID/admin_session');
        $request = Mage::app()->getRequest();

        if (!$session->isLoggedIn() && 'admin' === $request->getModuleName() && 'login' === $request->getActionName()) {
            if ($request->getParam('openid_mode')) {
                $session->login('', '', $request);
            } else {
                return parent::actionPreDispatchAdmin($event);
            }
        }
        return parent::actionPreDispatchAdmin($event);
    }
}
