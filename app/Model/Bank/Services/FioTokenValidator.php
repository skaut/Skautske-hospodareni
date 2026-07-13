<?php

declare(strict_types=1);

namespace App\Model\Bank\Services;

use App\Model\Bank\Fio\IDownloaderFactory;
use App\Model\Common\Embeddable\AccountNumber;
use Cake\Chronos\ChronosDate;
use FioApi\Exceptions\ConnectionException;
use FioApi\Exceptions\InternalErrorException;
use FioApi\Exceptions\InvalidResponseException;
use FioApi\Exceptions\TooGreedyException;
use InvalidArgumentException;

final class FioTokenValidator implements FioTokenValidatorInterface
{
    public function __construct(private readonly IDownloaderFactory $downloaderFactory)
    {
    }

    public function validate(AccountNumber $accountNumber, string $token): void
    {
        $today = ChronosDate::today()->toNative();

        try {
            $list = $this->downloaderFactory
                ->create($token)
                ->downloadFromTo($today, $today);
        } catch (TooGreedyException $exception) {
            throw new InvalidArgumentException('Token se teď nepodařilo ověřit, protože Fio dovoluje dotaz se stejným tokenem nejvýše jednou za 30 sekund. Zkuste uložení za chvíli znovu.', 0, $exception);
        } catch (InternalErrorException $exception) {
            throw new InvalidArgumentException('Fio token není platný nebo ještě není aktivní. Nově vytvořený token lze podle Fio použít až po 5 minutách od autorizace.', 0, $exception);
        } catch (ConnectionException|InvalidResponseException $exception) {
            throw new InvalidArgumentException('Token se nepodařilo ověřit kvůli chybě komunikace s Fio bankou. Zkuste uložení za chvíli znovu.', 0, $exception);
        }

        $intendedAccount = $accountNumber->getNumber().'/'.$accountNumber->getBankCode();
        $tokenAccount = $list->getAccount()->getAccountNumber().'/'.$list->getAccount()->getBankCode();
        if ($intendedAccount !== $tokenAccount) {
            throw new InvalidArgumentException(sprintf('Zadaný Fio token patří k účtu %s, ale ukládaný bankovní účet je %s.', $tokenAccount, $intendedAccount));
        }
    }
}
