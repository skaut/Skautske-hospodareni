<?php

declare(strict_types=1);

/**
 * Allow login without a password.
 */
class AdminerLoginPasswordLess
{
    protected string|null $username = null;

    protected string $password;

    public function __construct()
    {
        $this->username = getenv('ADMINER_USERNAME') ?: null;
        $this->password = getenv('ADMINER_PASSWORD') ?: '';
    }

    /** @return array */
    public function credentials(): array
    {
        $inputUsername = $_GET['username'];

        if ($this->isPasswordlessUser($inputUsername)) {
            return [SERVER, $inputUsername, $this->password];
        }

        return [SERVER, $inputUsername, get_password()];
    }

    public function login(string $username, string $password): bool
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
