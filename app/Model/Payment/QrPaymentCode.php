<?php

declare(strict_types=1);

namespace App\Model\Payment;

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\Result\PngResult;
use Endroid\QrCode\Writer\Result\SvgResult;
use Endroid\QrCode\Writer\SvgWriter;
use Rikudou\CzQrPayment\Exception\InvalidValueException;
use Rikudou\CzQrPayment\QrPayment;
use Throwable;

use function array_key_exists;
use function base64_encode;
use function htmlspecialchars;
use function preg_match;
use function preg_replace;

final class QrPaymentCode
{
    public static function buildImageUrl(
        string $bankAccount,
        float|string $amount,
        ?int $variableSymbol,
        ?int $constantSymbol,
        string $message,
        int $size = 200,
    ): string {
        return self::buildSvgResult($bankAccount, $amount, $variableSymbol, $constantSymbol, $message, $size)
            ->getDataUri();
    }

    public static function buildSvg(
        string $bankAccount,
        float|string $amount,
        ?int $variableSymbol,
        ?int $constantSymbol,
        string $message,
        int $size = 200,
    ): string {
        return self::buildSvgResult($bankAccount, $amount, $variableSymbol, $constantSymbol, $message, $size)
            ->getString();
    }

    public static function buildSvgWithCaption(
        string $bankAccount,
        float|string $amount,
        ?int $variableSymbol,
        ?int $constantSymbol,
        string $message,
        string $caption,
        int $size = 200,
    ): string {
        $captionHeight = 12;
        $svg = self::buildSvg($bankAccount, $amount, $variableSymbol, $constantSymbol, $message, $size);
        $height = $size + $captionHeight;
        $escapedCaption = htmlspecialchars($caption, ENT_XML1 | ENT_QUOTES, 'UTF-8');

        $svg = preg_replace('#height="[0-9.]+px"#', 'height="'.$height.'px"', $svg, 1) ?? $svg;
        $svg = preg_replace('#viewBox="0 0 ([0-9.]+) ([0-9.]+)"#', 'viewBox="0 0 $1 '.$height.'"', $svg, 1) ?? $svg;

        return preg_replace(
            '#</svg>\s*$#',
            '<text x="'.($size / 2).'" y="'.($size + 9).'" font-size="8" text-anchor="middle" fill="#000">'.$escapedCaption.'</text></svg>',
            $svg,
            1,
        ) ?? $svg;
    }

    public static function buildPng(
        string $bankAccount,
        float|string $amount,
        ?int $variableSymbol,
        ?int $constantSymbol,
        string $message,
        int $size = 200,
    ): string {
        try {
            $result = (new PngWriter())->write(self::buildQrCode($bankAccount, $amount, $variableSymbol, $constantSymbol, $message, $size));
            if (! $result instanceof PngResult) {
                throw new InvalidBankAccount();
            }

            return $result->getString();
        } catch (Throwable $e) {
            throw new InvalidBankAccount($e->getMessage(), previous: $e);
        }
    }

    public static function buildPngDataUri(
        string $bankAccount,
        float|string $amount,
        ?int $variableSymbol,
        ?int $constantSymbol,
        string $message,
        int $size = 200,
    ): string {
        return 'data:image/png;base64,'.base64_encode(
            self::buildPng($bankAccount, $amount, $variableSymbol, $constantSymbol, $message, $size),
        );
    }

    private static function buildSvgResult(
        string $bankAccount,
        float|string $amount,
        ?int $variableSymbol,
        ?int $constantSymbol,
        string $message,
        int $size,
    ): SvgResult {
        try {
            $result = (new SvgWriter())->write(
                self::buildQrCode($bankAccount, $amount, $variableSymbol, $constantSymbol, $message, $size),
                options: [
                    SvgWriter::WRITER_OPTION_EXCLUDE_XML_DECLARATION => true,
                ],
            );
            if (! $result instanceof SvgResult) {
                throw new InvalidBankAccount();
            }

            return $result;
        } catch (Throwable $e) {
            throw new InvalidBankAccount($e->getMessage(), previous: $e);
        }
    }

    private static function buildQrCode(
        string $bankAccount,
        float|string $amount,
        ?int $variableSymbol,
        ?int $constantSymbol,
        string $message,
        int $size,
    ): QrCode {
        $pattern = '#^((?P<prefix>[0-9]+)-)?(?P<number>[0-9]+)/(?P<code>[0-9]{4})$#';

        if (preg_match($pattern, $bankAccount, $account) !== 1) {
            throw new InvalidBankAccount();
        }

        $accountNumber = $account['number'];
        if (array_key_exists('prefix', $account) && $account['prefix'] !== '') {
            $accountNumber = $account['prefix'].'-'.$accountNumber;
        }

        try {
            $payment = QrPayment::fromAccountAndBankCode($accountNumber, $account['code']);
            $payment->setAmount((float) $amount);
            $payment->setCurrency('CZK');
            $payment->setVariableSymbol($variableSymbol);
            $payment->setConstantSymbol($constantSymbol);
            $payment->setComment($message !== '' ? $message : null);

            return new QrCode($payment->getQrString(), size: $size, margin: 2);
        } catch (InvalidValueException) {
            throw new InvalidBankAccount();
        } catch (Throwable $e) {
            throw new InvalidBankAccount($e->getMessage(), previous: $e);
        }
    }
}
