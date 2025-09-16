<?php

/**
 * Allow login without a password.
 */
class AdminerLoginPasswordLess
{
    /**
     * @var string|null
     */
    protected $username;

    /**
     * @var string
     */
    protected $password;

    /**
     * AdminerLoginPasswordLess constructor.
     */
    public function __construct()
    {
        $this->username = getenv('ADMINER_USERNAME') ?: null;
        $this->password = getenv('ADMINER_PASSWORD') ?: '';
    }

    /**
     * @return array
     */
    public function credentials()
    {
        $server = $_GET['server'] ?? 'localhost';
        $inputUsername = $_GET['username'] ?? '';

        if ($this->isPasswordlessUser($inputUsername)) {
            return [$server, $inputUsername, $this->password];
        }

        $inputPassword = $_GET['password'] ?? '';

        return [$server, $inputUsername, $inputPassword];
    }

    /**
     * @param  string $username
     * @param  string $password
     * @return bool
     */
    public function login($username, $password)
    {
        if ($this->isPasswordlessUser($username)) {
            return true;
        }

        if ($password === '') {
            return lang('Adminer does not support accessing a database without a password, <a href="https://www.adminer.org/en/password/"%s>more information</a>.', target_blank());
        }

        return true;
    }

    public function isPasswordlessUser(string $username): bool
    {
        return $this->username !== null && $this->username === $username;
    }
}
