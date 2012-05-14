<?php
class TestUserStorage implements IUserStorage {

    private $identity;

    /**
     * Sets the authenticated status of this user.
     * @param  bool
     * @return void
     */
    function setAuthenticated($state) {}

    /**
     * Is this user authenticated?
     * @return bool
     */
    function isAuthenticated() {
        return ($this->identity != null);
    }

    /**
     * Sets the user identity.
     * @return void
     */
    public function setIdentity(IIdentity $identity = NULL) {
        $this->identity = $identity;
        return $this;
    }

    /**
     * Returns current user identity, if any.
     * @return IIdentity|NULL
     */
    function getIdentity() {
        return $this->identity;
    }

    /**
     * Enables log out from the persistent storage after inactivity.
     * @return void
     */
    function setExpiration($time, $flags = 0) {}

    /**
     * Why was user logged out?
     * @return int
     */
    function getLogoutReason() {
        return 1;
    }
}