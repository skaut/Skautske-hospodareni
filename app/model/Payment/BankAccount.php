<?php
/**
 * Created by PhpStorm.
 * User: fmasa
 * Date: 22.2.17
 * Time: 0:23
 */

namespace Model\Payment;


class BankAccount
{

    /** @var string */
    private $number;

    /**
     * BankAccount constructor.
     * @param string $number
     */
    public function __construct(string $number)
    {
        $this->number = $number;
    }

    /**
     * @return string
     */
    public function getNumber(): string
    {
        return $this->number;
    }

}