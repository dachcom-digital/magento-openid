<?php

/**
 * Admin user model
 * @category   Dachcom
 * @package    Dachcom_OpenID
 */
class Dachcom_OpenID_Model_Admin_User extends Mage_Admin_Model_User {
    /**
     * Authenticate user name and save loaded record
     * When this method gets called, the user is already authenticated through OpenID
     *
     * @param string $username
     * @param string $password
     * @return boolean
     * @throws Mage_Core_Exception
     */
    public function authenticate($username, $password) {
        $result = false;
        $identity = $username;

        try {
            Mage::dispatchEvent('admin_user_authenticate_before', array(
                'username' => $identity,
                'user'     => $this
            ));

            $users = Mage::getStoreConfig('Dachcom_OpenID/users');

            $username = '';
            foreach ($users as $user => $userIdentity) {
                if (rtrim($userIdentity, '/') === rtrim($identity, '/')) {
                    $username = $user;
                    break;
                }
            }

            $this->loadByUsername($username);

            if ($this->getId()) {
                if ($this->getIsActive() != '1') {
                    Mage::throwException(Mage::helper('adminhtml')->__('This account is inactive.'));
                }
                if (!$this->hasAssigned2Role($this->getId())) {
                    Mage::throwException(Mage::helper('adminhtml')->__('Access denied.'));
                }
                $result = true;
            }

            Mage::dispatchEvent('admin_user_authenticate_after', array(
                'username' => $username,
                'password' => $password,
                'user'     => $this,
                'result'   => $result,
            ));
        }
        catch (Mage_Core_Exception $e) {
            $this->unsetData();
            throw $e;
        }

        if (!$result) {
            $this->unsetData();
        }
        return $result;
    }
}
