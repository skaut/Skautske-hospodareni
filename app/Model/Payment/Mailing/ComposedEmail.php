<?php

declare(strict_types=1);

namespace App\Model\Payment\Mailing;

use App\Model\Google\Entity\GoogleOAuth;
use Nette\Mail\Message;

/** A ready e-mail and the Google account that sends it. */
final class ComposedEmail
{
    public function __construct(public readonly Message $message, public readonly GoogleOAuth $sender)
    {
    }
}
