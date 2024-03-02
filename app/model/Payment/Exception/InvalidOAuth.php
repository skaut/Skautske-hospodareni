<?php

declare(strict_types=1);

namespace Model\Google;

use Exception;

use function sprintf;

class InvalidOAuth extends Exception
{
    public function getExplainedMessage(): string
    {
        return sprintf('Chyba při odesílání e-mailu. Nejspíš vypršela platnost propojení. Zkuste emailový účet odebrat a znovu přidat. (Chyba: %s)', $this->message);
    }
}
