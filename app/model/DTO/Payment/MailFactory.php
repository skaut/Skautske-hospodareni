<?php


namespace Model\DTO\Payment;


use Dibi\Row;

class MailFactory
{

    public static function create(Row $row) : Mail
    {
        return new Mail($row->id, $row->unitId, $row->username, $row->host, $row->secure);
    }

}
