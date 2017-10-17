<?php

namespace Model\Payment;

use Nette\Mail\IMailer;
use Nette\Mail\Message;

class NullMailer implements IMailer
{

    function send(Message $mail)
    {
    }

}
