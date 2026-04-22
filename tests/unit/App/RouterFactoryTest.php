<?php

declare(strict_types=1);

namespace App;

use Codeception\Test\Unit;
use Nette\Http\Request as HttpRequest;
use Nette\Http\UrlScript;
use Nette\Routing\Router;

use function array_key_exists;
use function parse_url;

final class RouterFactoryTest extends Unit
{
    /**
     * @dataProvider provideCanonicalPaymentRoutesForConstruction
     *
     * @param array<string, int|string> $params
     */
    public function testConstructsCanonicalPaymentRoutes(array $params, string $expectedPath): void
    {
        $url = $this->createRouter()->constructUrl($params, new UrlScript('https://example.test/'));

        self::assertNotNull($url);
        self::assertSame($expectedPath, $this->pathWithQuery($url));
    }

    /**
     * @dataProvider provideCanonicalPaymentRoutesForMatching
     *
     * @param array<string, int|string> $expectedParameters
     */
    public function testMatchesCanonicalPaymentRoutes(string $url, array $expectedParameters): void
    {
        $request = $this->createRouter()->match($this->createHttpRequest($url));

        self::assertIsArray($request);

        foreach ($expectedParameters as $name => $expectedValue) {
            self::assertTrue(array_key_exists($name, $request));
            self::assertSame((string) $expectedValue, (string) $request[$name]);
        }
    }

    /**
     * @return list<array{0: array<string, int|string>, 1: string}>
     */
    public function provideCanonicalPaymentRoutesForConstruction(): array
    {
        return [
            [['presenter' => 'Unit:Cashbook'], '/jednotka'],
            [['presenter' => 'Unit:Cashbook', 'unitId' => 5], '/jednotka/5/kniha'],
            [['presenter' => 'Unit:Cashbook', 'unitId' => 5, 'year' => 2028], '/jednotka/5/kniha?rok=2028'],
            [['presenter' => 'Unit:Chit', 'unitId' => 5, 'year' => 2028], '/jednotka/5/paragony?rok=2028'],
            [['presenter' => 'Unit:Chit', 'unitId' => 5, 'year' => 2028, 'onlyUnlocked' => 0], '/jednotka/5/paragony?rok=2028&onlyUnlocked=0'],
            [['presenter' => 'Unit:Budget', 'unitId' => 5], '/jednotka/5/rozpocet'],
            [['presenter' => 'Unit:Budget', 'action' => 'add', 'unitId' => 5, 'year' => 2028], '/jednotka/5/rozpocet/pridat?rok=2028'],
            [['presenter' => 'Travel:Default'], '/cestaky'],
            [['presenter' => 'Travel:Command'], '/cestaky/prikazy/new'],
            [['presenter' => 'Travel:Command', 'action' => 'detail', 'id' => 77], '/cestaky/prikazy/77'],
            [['presenter' => 'Travel:Command', 'action' => 'edit', 'id' => 77], '/cestaky/prikazy/77/edit'],
            [['presenter' => 'Travel:Command', 'action' => 'print', 'id' => 77], '/cestaky/prikazy/77/print'],
            [['presenter' => 'Travel:VehicleList'], '/cestaky/vozidla'],
            [['presenter' => 'Travel:Vehicle', 'action' => 'new'], '/cestaky/vozidla/new'],
            [['presenter' => 'Travel:Vehicle', 'action' => 'detail', 'id' => 77], '/cestaky/vozidla/detail/77'],
            [['presenter' => 'Travel:Vehicle', 'action' => 'downloadScan', 'id' => 77, 'path' => 'scan.pdf'], '/cestaky/vozidla/download-scan/77?path=scan.pdf'],
            [['presenter' => 'Travel:Contract'], '/cestaky/smlouvy'],
            [['presenter' => 'Travel:Contract', 'action' => 'new'], '/cestaky/smlouvy/new'],
            [['presenter' => 'Travel:Contract', 'action' => 'detail', 'id' => 77], '/cestaky/smlouvy/detail/77'],
            [['presenter' => 'Travel:Contract', 'action' => 'print', 'id' => 77], '/cestaky/smlouvy/print/77'],
            [['presenter' => 'Events:Default'], '/akce'],
            [['presenter' => 'Events:NewEvent'], '/akce/nova'],
            [['presenter' => 'Events:Event', 'aid' => 42], '/akce/42'],
            [['presenter' => 'Events:Participant', 'aid' => 42], '/akce/42/ucastnici'],
            [['presenter' => 'Events:Cashbook', 'aid' => 42], '/akce/42/kniha'],
            [['presenter' => 'Events:Privileges', 'aid' => 42], '/akce/42/opravneni'],
            [['presenter' => 'Camps:Default'], '/tabory'],
            [['presenter' => 'Camps:Detail', 'aid' => 42], '/tabory/42'],
            [['presenter' => 'Camps:Participant', 'aid' => 42], '/tabory/42/ucastnici'],
            [['presenter' => 'Camps:Cashbook', 'aid' => 42], '/tabory/42/kniha'],
            [['presenter' => 'Camps:Budget', 'aid' => 42], '/tabory/42/rozpocet'],
            [['presenter' => 'Education:Default'], '/vzdelavacky'],
            [['presenter' => 'Education:Education', 'aid' => 42], '/vzdelavacky/42'],
            [['presenter' => 'Education:Participant', 'aid' => 42], '/vzdelavacky/42/ucastnici'],
            [['presenter' => 'Education:Cashbook', 'aid' => 42], '/vzdelavacky/42/kniha'],
            [['presenter' => 'Education:Budget', 'aid' => 42], '/vzdelavacky/42/rozpocet'],
            [['presenter' => 'Education:Privileges', 'aid' => 42], '/vzdelavacky/42/opravneni'],
            [['presenter' => 'Settings:Default', 'unitId' => 5], '/nastaveni?jednotka=5'],
            [['presenter' => 'Settings:Automation', 'unitId' => 5], '/nastaveni/automatizace?jednotka=5'],
            [['presenter' => 'Payments:Dashboard'], '/platby'],
            [['presenter' => 'Payments:GroupList'], '/platby/skupiny'],
            [['presenter' => 'Payments:Group', 'action' => 'newGroup'], '/platby/skupiny/nova'],
            [['presenter' => 'Payments:Group', 'action' => 'edit', 'id' => 321], '/platby/skupiny/321/upravit'],
            [['presenter' => 'Payments:Participants', 'id' => 321], '/platby/skupiny/321/ucastnici'],
            [['presenter' => 'Payments:People', 'id' => 321], '/platby/skupiny/321/osoby'],
            [['presenter' => 'Payments:Journal', 'groupId' => 321], '/platby/skupiny/321/casopisy'],
            [['presenter' => 'Payments:Repayment', 'id' => 321], '/platby/skupiny/321/vratky'],
            [['presenter' => 'Payments:Payment', 'action' => 'default', 'id' => 321], '/platby/skupiny/321/platby'],
            [['presenter' => 'Payments:InvoiceList', 'action' => 'default', 'unitId' => 5], '/platby/faktury?jednotka=5'],
            [['presenter' => 'Payments:InvoiceList', 'action' => 'create', 'invoiceSequenceId' => 9, 'unitId' => 5], '/platby/rady/9/nova?jednotka=5'],
            [['presenter' => 'Payments:InvoiceList', 'action' => 'edit', 'id' => 77], '/platby/faktury/77/upravit'],
            [['presenter' => 'Payments:InvoiceList', 'action' => 'detail', 'id' => 77], '/platby/faktury/77'],
            [['presenter' => 'Payments:InvoiceSequenceList', 'unitId' => 5], '/platby/rady?jednotka=5'],
            [['presenter' => 'Payments:InvoiceSequence', 'unitId' => 5], '/platby/rady/nova?jednotka=5'],
            [['presenter' => 'Payments:InvoiceSequence', 'action' => 'edit', 'id' => 9, 'unitId' => 5], '/platby/rady/9/upravit?jednotka=5'],
            [['presenter' => 'Payments:InvoiceList', 'invoiceSequenceId' => 9, 'unitId' => 5], '/platby/rady/9?jednotka=5'],
            [['presenter' => 'Settings:BankAccounts', 'unitId' => 5], '/nastaveni/bankovni-ucty?jednotka=5'],
            [['presenter' => 'Settings:BankAccounts', 'action' => 'new', 'unitId' => 5], '/nastaveni/bankovni-ucty/novy?jednotka=5'],
            [['presenter' => 'Settings:BankAccounts', 'action' => 'detail', 'id' => 77, 'unitId' => 5], '/nastaveni/bankovni-ucty/77?jednotka=5'],
            [['presenter' => 'Settings:BankAccounts', 'action' => 'edit', 'id' => 77, 'unitId' => 5], '/nastaveni/bankovni-ucty/77/upravit?jednotka=5'],
            [['presenter' => 'Settings:Mails', 'unitId' => 5], '/nastaveni/emaily?jednotka=5'],
            [['presenter' => 'Settings:Invoices', 'unitId' => 5, 'year' => 2028], '/nastaveni/faktury?jednotka=5&rok=2028'],
            [['presenter' => 'Payments:RegistrationCreateGroup'], '/platby/registrace/nova'],
            [['presenter' => 'Payments:RegistrationAddMembers', 'id' => 12], '/platby/registrace/12/osoby'],
            [['presenter' => 'Payments:Journal', 'groupId' => 12], '/platby/skupiny/12/casopisy'],
            [['presenter' => 'Payments:CampSelectForGroup'], '/platby/tabory'],
            [['presenter' => 'Payments:CampCreateGroup', 'campId' => 4], '/platby/tabory/4/nova'],
            [['presenter' => 'Payments:EventSelectForGroup'], '/platby/akce'],
            [['presenter' => 'Payments:EventCreateGroup', 'eventId' => 4], '/platby/akce/4/nova'],
            [['presenter' => 'Payments:EducationSelectForGroup'], '/platby/vzdelavacky'],
            [['presenter' => 'Payments:EducationCreateGroup', 'educationId' => 4], '/platby/vzdelavacky/4/nova'],
        ];
    }

    /**
     * @return list<array{0: string, 1: array<string, int|string>}>
     */
    public function provideCanonicalPaymentRoutesForMatching(): array
    {
        return [
            ['/jednotka', ['presenter' => 'Unit:Cashbook', 'action' => 'default']],
            ['/jednotka/5/kniha', ['presenter' => 'Unit:Cashbook', 'action' => 'default', 'unitId' => 5]],
            ['/jednotka/5/kniha?rok=2028', ['presenter' => 'Unit:Cashbook', 'action' => 'default', 'unitId' => 5, 'year' => 2028]],
            ['/jednotka?jednotka=5&rok=2028', ['presenter' => 'Unit:Cashbook', 'action' => 'default', 'unitId' => 5, 'year' => 2028]],
            ['/jednotka/5/paragony?rok=2028', ['presenter' => 'Unit:Chit', 'action' => 'default', 'unitId' => 5, 'year' => 2028]],
            ['/jednotka/5/paragony?rok=2028&onlyUnlocked=0', ['presenter' => 'Unit:Chit', 'action' => 'default', 'unitId' => 5, 'year' => 2028, 'onlyUnlocked' => 0]],
            ['/jednotka/5/rozpocet', ['presenter' => 'Unit:Budget', 'action' => 'default', 'unitId' => 5]],
            ['/jednotka/5/rozpocet/pridat?rok=2028', ['presenter' => 'Unit:Budget', 'action' => 'add', 'unitId' => 5, 'year' => 2028]],
            ['/cestaky', ['presenter' => 'Travel:Default', 'action' => 'default']],
            ['/cestaky/prikazy/new', ['presenter' => 'Travel:Command', 'action' => 'default']],
            ['/cestaky/prikazy/77', ['presenter' => 'Travel:Command', 'action' => 'detail', 'id' => 77]],
            ['/cestaky/prikazy/77/edit', ['presenter' => 'Travel:Command', 'action' => 'edit', 'id' => 77]],
            ['/cestaky/prikazy/77/print', ['presenter' => 'Travel:Command', 'action' => 'print', 'id' => 77]],
            ['/cestaky/vozidla', ['presenter' => 'Travel:VehicleList', 'action' => 'default']],
            ['/cestaky/vozidla/new', ['presenter' => 'Travel:Vehicle', 'action' => 'new']],
            ['/cestaky/vozidla/detail/77', ['presenter' => 'Travel:Vehicle', 'action' => 'detail', 'id' => 77]],
            ['/cestaky/vozidla/download-scan/77?path=scan.pdf', ['presenter' => 'Travel:Vehicle', 'action' => 'downloadScan', 'id' => 77, 'path' => 'scan.pdf']],
            ['/cestaky/smlouvy', ['presenter' => 'Travel:Contract', 'action' => 'default']],
            ['/cestaky/smlouvy/new', ['presenter' => 'Travel:Contract', 'action' => 'new']],
            ['/cestaky/smlouvy/detail/77', ['presenter' => 'Travel:Contract', 'action' => 'detail', 'id' => 77]],
            ['/cestaky/smlouvy/print/77', ['presenter' => 'Travel:Contract', 'action' => 'print', 'id' => 77]],
            ['/vzdelavacky', ['presenter' => 'Education:Default', 'action' => 'default']],
            ['/vzdelavacky/42', ['presenter' => 'Education:Education', 'action' => 'default', 'aid' => 42]],
            ['/vzdelavacky/42/ucastnici', ['presenter' => 'Education:Participant', 'action' => 'default', 'aid' => 42]],
            ['/vzdelavacky/42/kniha', ['presenter' => 'Education:Cashbook', 'action' => 'default', 'aid' => 42]],
            ['/vzdelavacky/42/rozpocet', ['presenter' => 'Education:Budget', 'action' => 'default', 'aid' => 42]],
            ['/vzdelavacky/42/opravneni', ['presenter' => 'Education:Privileges', 'action' => 'default', 'aid' => 42]],
            ['/tabory', ['presenter' => 'Camps:Default', 'action' => 'default']],
            ['/tabory/42', ['presenter' => 'Camps:Detail', 'action' => 'default', 'aid' => 42]],
            ['/tabory/42/ucastnici', ['presenter' => 'Camps:Participant', 'action' => 'default', 'aid' => 42]],
            ['/tabory/42/kniha', ['presenter' => 'Camps:Cashbook', 'action' => 'default', 'aid' => 42]],
            ['/tabory/42/rozpocet', ['presenter' => 'Camps:Budget', 'action' => 'default', 'aid' => 42]],
            ['/akce', ['presenter' => 'Events:Default', 'action' => 'default']],
            ['/akce/nova', ['presenter' => 'Events:NewEvent', 'action' => 'default']],
            ['/akce/42', ['presenter' => 'Events:Event', 'action' => 'default', 'aid' => 42]],
            ['/akce/42/ucastnici', ['presenter' => 'Events:Participant', 'action' => 'default', 'aid' => 42]],
            ['/akce/42/kniha', ['presenter' => 'Events:Cashbook', 'action' => 'default', 'aid' => 42]],
            ['/akce/42/opravneni', ['presenter' => 'Events:Privileges', 'action' => 'default', 'aid' => 42]],
            ['/nastaveni?jednotka=5', ['presenter' => 'Settings:Default', 'action' => 'default', 'unitId' => 5]],
            ['/nastaveni/automatizace?jednotka=5', ['presenter' => 'Settings:Automation', 'action' => 'default', 'unitId' => 5]],
            ['/platby', ['presenter' => 'Payments:Dashboard', 'action' => 'default']],
            ['/platby/skupiny', ['presenter' => 'Payments:GroupList', 'action' => 'default']],
            ['/platby/skupiny/nova', ['presenter' => 'Payments:Group', 'action' => 'newGroup']],
            ['/platby/skupiny/321/upravit', ['presenter' => 'Payments:Group', 'action' => 'edit', 'id' => 321]],
            ['/platby/skupiny/321/ucastnici', ['presenter' => 'Payments:Participants', 'action' => 'default', 'id' => 321]],
            ['/platby/skupiny/321/osoby', ['presenter' => 'Payments:People', 'action' => 'default', 'id' => 321]],
            ['/platby/skupiny/321/casopisy', ['presenter' => 'Payments:Journal', 'action' => 'default', 'groupId' => 321]],
            ['/platby/skupiny/321/vratky', ['presenter' => 'Payments:Repayment', 'action' => 'default', 'id' => 321]],
            ['/platby/skupiny/321/platby', ['presenter' => 'Payments:Payment', 'action' => 'default', 'id' => 321]],
            ['/platby/faktury?jednotka=5', ['presenter' => 'Payments:InvoiceList', 'action' => 'default', 'unitId' => 5]],
            ['/platby/rady/9/nova?jednotka=5', ['presenter' => 'Payments:InvoiceList', 'action' => 'create', 'invoiceSequenceId' => 9, 'unitId' => 5]],
            ['/platby/faktury/77/upravit', ['presenter' => 'Payments:InvoiceList', 'action' => 'edit', 'id' => 77]],
            ['/platby/faktury/77', ['presenter' => 'Payments:InvoiceList', 'action' => 'detail', 'id' => 77]],
            ['/platby/rady?jednotka=5', ['presenter' => 'Payments:InvoiceSequenceList', 'action' => 'default', 'unitId' => 5]],
            ['/platby/rady/nova?jednotka=5', ['presenter' => 'Payments:InvoiceSequence', 'action' => 'default', 'unitId' => 5]],
            ['/platby/rady/9/upravit?jednotka=5', ['presenter' => 'Payments:InvoiceSequence', 'action' => 'edit', 'id' => 9, 'unitId' => 5]],
            ['/platby/rady/9?jednotka=5', ['presenter' => 'Payments:InvoiceList', 'action' => 'default', 'invoiceSequenceId' => 9, 'unitId' => 5]],
            ['/nastaveni/bankovni-ucty?jednotka=5', ['presenter' => 'Settings:BankAccounts', 'action' => 'default', 'unitId' => 5]],
            ['/nastaveni/bankovni-ucty/novy?jednotka=5', ['presenter' => 'Settings:BankAccounts', 'action' => 'new', 'unitId' => 5]],
            ['/nastaveni/bankovni-ucty/77?jednotka=5', ['presenter' => 'Settings:BankAccounts', 'action' => 'detail', 'id' => 77, 'unitId' => 5]],
            ['/nastaveni/bankovni-ucty/77/upravit?jednotka=5', ['presenter' => 'Settings:BankAccounts', 'action' => 'edit', 'id' => 77, 'unitId' => 5]],
            ['/nastaveni/emaily?jednotka=5', ['presenter' => 'Settings:Mails', 'action' => 'default', 'unitId' => 5]],
            ['/nastaveni/faktury?jednotka=5&rok=2028', ['presenter' => 'Settings:Invoices', 'action' => 'default', 'unitId' => 5, 'year' => 2028]],
            ['/platby/registrace/nova', ['presenter' => 'Payments:RegistrationCreateGroup', 'action' => 'default']],
            ['/platby/registrace/12/osoby', ['presenter' => 'Payments:RegistrationAddMembers', 'action' => 'default', 'id' => 12]],
            ['/platby/registrace/12/casopisy', ['presenter' => 'Payments:RegistrationJournal', 'action' => 'default', 'groupId' => 12]],
            ['/platby/tabory', ['presenter' => 'Payments:CampSelectForGroup', 'action' => 'default']],
            ['/platby/tabory/4/nova', ['presenter' => 'Payments:CampCreateGroup', 'action' => 'default', 'campId' => 4]],
            ['/platby/akce', ['presenter' => 'Payments:EventSelectForGroup', 'action' => 'default']],
            ['/platby/akce/4/nova', ['presenter' => 'Payments:EventCreateGroup', 'action' => 'default', 'eventId' => 4]],
            ['/platby/vzdelavacky', ['presenter' => 'Payments:EducationSelectForGroup', 'action' => 'default']],
            ['/platby/vzdelavacky/4/nova', ['presenter' => 'Payments:EducationCreateGroup', 'action' => 'default', 'educationId' => 4]],
        ];
    }

    private function createRouter(): Router
    {
        return (new RouterFactory())->createRouter();
    }

    private function createHttpRequest(string $pathAndQuery): HttpRequest
    {
        return new HttpRequest(new UrlScript('https://example.test'.$pathAndQuery, '/'));
    }

    private function pathWithQuery(string $url): string
    {
        $parts = parse_url($url);
        self::assertIsArray($parts);

        $path = $parts['path'] ?? '';

        if (array_key_exists('query', $parts)) {
            $path .= '?'.$parts['query'];
        }

        return $path;
    }
}
