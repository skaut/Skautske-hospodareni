<?php

declare(strict_types=1);

namespace App\Model\Google;

use Exception;

use function sprintf;

/**
 * Google refused to give an access token for a connected account, so nothing can be sent from it.
 * The message keeps Google's answer for the logs, {@see getExplainedMessage()} is for people.
 */
class InvalidOAuth extends Exception
{
    /** Google refuses the token itself (e.g. the owner revoked the access), only connecting the account again helps. */
    private const REVOKED = 'invalid_grant';

    public function __construct(private string $email, private string $error, ?string $description = null)
    {
        parent::__construct($description === null ? $error : sprintf('%s => %s', $error, $description));
    }

    /** Google refused the account earlier, so the application does not ask it again. */
    public static function remembered(string $email): self
    {
        return new self($email, self::REVOKED, 'refused earlier, waiting until the account is connected again');
    }

    public function isRevoked(): bool
    {
        return $this->error === self::REVOKED;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getExplainedMessage(): string
    {
        if ($this->isRevoked()) {
            return sprintf(
                'Propojení účtu %1$s s Gmailem přestalo platit, Google e-mail odmítl odeslat. Propojte účet znovu: v Nastavení → E-maily jednotky, ke které účet patří, klikněte na „Propojit účet“ a přihlaste se účtem %1$s.',
                $this->email,
            );
        }

        return sprintf(
            'Google odmítl přístup k účtu %s (chyba %s). Zkuste účet znovu propojit v Nastavení → E-maily. Když to nepomůže, dejte vědět správci aplikace.',
            $this->email,
            $this->error,
        );
    }
}
