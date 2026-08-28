<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260827120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Store contextual page help in the database so it can be edited in the administration';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE page_help (id INT UNSIGNED AUTO_INCREMENT NOT NULL, page_key VARCHAR(191) NOT NULL, sections JSON NOT NULL, updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_by_name VARCHAR(255) DEFAULT NULL, UNIQUE INDEX page_help_page_key_uniq (page_key), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_czech_ci` ENGINE = InnoDB');

        // Move the help that was until now hard-coded in the templates.
        $this->addSql(
            'INSERT INTO page_help (page_key, sections, updated_at, updated_by_name) VALUES (?, ?, NOW(), NULL)',
            ['BugReport:default', '[{"heading":"Co uvést","text":"Popište kroky před chybou, očekávaný výsledek, skutečný výsledek a přibližný čas, kdy se chyba stala.","items":[]},{"heading":"URL stránky","text":"Adresa není povinná. Pokud jste formulář otevřeli z patičky chybné stránky, vyplní se automaticky.","items":[]},{"heading":"Automatická diagnostika","text":"K hlášení se připojí identifikace přihlášeného uživatele, aktivní role a jednotka, IP adresa, release aplikace, prohlížeč, rozlišení, časové pásmo a další technické údaje potřebné k dohledání související události v logu nebo Sentry.","items":[]}]'],
        );
        $this->addSql(
            'INSERT INTO page_help (page_key, sections, updated_at, updated_by_name) VALUES (?, ?, NOW(), NULL)',
            ['Payments:CampCreateGroup:default', '[{"heading":"E-mail odesílatele","text":"E-mail, ze kterého se budou zasílat informace o platbách.","items":[]},{"heading":"Proměnné v e-mailu","text":"Proměnné v e-mailu budou před odesláním nahrazeny údaji z platby. Jsou zapsány formou: \\"%Název_proměnné%\\".","items":["%account% - celé číslo účtu i s předčíslím a kódem banky","%qrcode% - vygenerovaný obrázek QR kódu (nelze použít v předmětu)","%name% - název/účel platby","%groupname% - název platební skupiny","%amount% - částka","%maturity% - splatnost","%maturityus% - splatnost v americkém formátu (např. 2022-12-24)","%vs% - variabilní symbol","%ks% - konstantní symbol","%note% - poznámka","%user% - jméno uživatele, který e-mail odeslal"]}]'],
        );
        $this->addSql(
            'INSERT INTO page_help (page_key, sections, updated_at, updated_by_name) VALUES (?, ?, NOW(), NULL)',
            ['Payments:EducationCreateGroup:default', '[{"heading":"E-mail odesílatele","text":"E-mail, ze kterého se budou zasílat informace o platbách.","items":[]},{"heading":"Proměnné v e-mailu","text":"Proměnné v e-mailu budou před odesláním nahrazeny údaji z platby. Jsou zapsány formou: \\"%Název_proměnné%\\".","items":["%account% - celé číslo účtu i s předčíslím a kódem banky","%qrcode% - vygenerovaný obrázek QR kódu (nelze použít v předmětu)","%name% - název/účel platby","%groupname% - název platební skupiny","%amount% - částka","%maturity% - splatnost","%maturityus% - splatnost v americkém formátu (např. 2022-12-24)","%vs% - variabilní symbol","%ks% - konstantní symbol","%note% - poznámka","%user% - jméno uživatele, který e-mail odeslal"]}]'],
        );
        $this->addSql(
            'INSERT INTO page_help (page_key, sections, updated_at, updated_by_name) VALUES (?, ?, NOW(), NULL)',
            ['Payments:EventCreateGroup:default', '[{"heading":"E-mail odesílatele","text":"E-mail, ze kterého se budou zasílat informace o platbách.","items":[]},{"heading":"Proměnné v e-mailu","text":"Proměnné v e-mailu budou před odesláním nahrazeny údaji z platby. Jsou zapsány formou: \\"%Název_proměnné%\\".","items":["%account% - celé číslo účtu i s předčíslím a kódem banky","%qrcode% - vygenerovaný obrázek QR kódu (nelze použít v předmětu)","%name% - název/účel platby","%groupname% - název platební skupiny","%amount% - částka","%maturity% - splatnost","%maturityus% - splatnost v americkém formátu (např. 2022-12-24)","%vs% - variabilní symbol","%ks% - konstantní symbol","%note% - poznámka","%user% - jméno uživatele, který e-mail odeslal"]}]'],
        );
        $this->addSql(
            'INSERT INTO page_help (page_key, sections, updated_at, updated_by_name) VALUES (?, ?, NOW(), NULL)',
            ['Payments:Group:clone', '[{"heading":"E-mail odesílatele","text":"E-mail, ze kterého se budou zasílat informace o platbách.","items":[]},{"heading":"Proměnné v e-mailu","text":"Proměnné v e-mailu budou před odesláním nahrazeny údaji z platby. Jsou zapsány formou: \\"%Název_proměnné%\\".","items":["%account% - celé číslo účtu i s předčíslím a kódem banky","%qrcode% - vygenerovaný obrázek QR kódu (nelze použít v předmětu)","%name% - název/účel platby","%groupname% - název platební skupiny","%amount% - částka","%maturity% - splatnost","%maturityus% - splatnost v americkém formátu (např. 2022-12-24)","%vs% - variabilní symbol","%ks% - konstantní symbol","%note% - poznámka","%user% - jméno uživatele, který e-mail odeslal"]}]'],
        );
        $this->addSql(
            'INSERT INTO page_help (page_key, sections, updated_at, updated_by_name) VALUES (?, ?, NOW(), NULL)',
            ['Payments:Group:edit', '[{"heading":"E-mail odesílatele","text":"E-mail, ze kterého se budou zasílat informace o platbách.","items":[]},{"heading":"Proměnné v e-mailu","text":"Proměnné v e-mailu budou před odesláním nahrazeny údaji z platby. Jsou zapsány formou: \\"%Název_proměnné%\\".","items":["%account% - celé číslo účtu i s předčíslím a kódem banky","%qrcode% - vygenerovaný obrázek QR kódu (nelze použít v předmětu)","%name% - název/účel platby","%groupname% - název platební skupiny","%amount% - částka","%maturity% - splatnost","%maturityus% - splatnost v americkém formátu (např. 2022-12-24)","%vs% - variabilní symbol","%ks% - konstantní symbol","%note% - poznámka","%user% - jméno uživatele, který e-mail odeslal"]}]'],
        );
        $this->addSql(
            'INSERT INTO page_help (page_key, sections, updated_at, updated_by_name) VALUES (?, ?, NOW(), NULL)',
            ['Payments:Group:newGroup', '[{"heading":"E-mail odesílatele","text":"E-mail, ze kterého se budou zasílat informace o platbách.","items":[]},{"heading":"Proměnné v e-mailu","text":"Proměnné v e-mailu budou před odesláním nahrazeny údaji z platby. Jsou zapsány formou: \\"%Název_proměnné%\\".","items":["%account% - celé číslo účtu i s předčíslím a kódem banky","%qrcode% - vygenerovaný obrázek QR kódu (nelze použít v předmětu)","%name% - název/účel platby","%groupname% - název platební skupiny","%amount% - částka","%maturity% - splatnost","%maturityus% - splatnost v americkém formátu (např. 2022-12-24)","%vs% - variabilní symbol","%ks% - konstantní symbol","%note% - poznámka","%user% - jméno uživatele, který e-mail odeslal"]}]'],
        );
        $this->addSql(
            'INSERT INTO page_help (page_key, sections, updated_at, updated_by_name) VALUES (?, ?, NOW(), NULL)',
            ['Payments:GroupList:default', '[{"heading":"Platební skupina","text":"Skupina sdružuje související platby, jejich stav a způsob párování úhrad.","items":[]},{"heading":"Hledání a filtry","text":"Vyhledávejte podle názvu nebo jednotky. Pro dokončené skupiny přepněte filtr v hlavičce seznamu.","items":[]}]'],
        );
        $this->addSql(
            'INSERT INTO page_help (page_key, sections, updated_at, updated_by_name) VALUES (?, ?, NOW(), NULL)',
            ['Payments:InvoiceSequence:default', '[{"heading":"Číselná řada","text":"Číslo faktury a VS se generují automaticky podle `Prefixu` a `Prvního čísla v řadě`.","items":[]},{"heading":"E-mail odesílatele","text":"E-mail, ze kterého se budou zasílat informace o fakturách.","items":[]},{"heading":"Proměnné v e-mailu","text":"Proměnné v e-mailu budou před odesláním nahrazeny údaji z faktury. Jsou zapsány formou: \\"%Název_proměnné%\\".","items":["%number% - číslo faktury","%customer_name% - jméno / název odběratele","%amount% - celková částka faktury","%maturity% - splatnost","%maturityus% - splatnost v americkém formátu (např. 2026-12-24)","%vs% - variabilní symbol","%user% - jméno uživatele, který e-mail odeslal"]}]'],
        );
        $this->addSql(
            'INSERT INTO page_help (page_key, sections, updated_at, updated_by_name) VALUES (?, ?, NOW(), NULL)',
            ['Payments:InvoiceSequence:edit', '[{"heading":"Číselná řada","text":"Číslo faktury a VS se generují automaticky podle `Prefixu` a `Prvního čísla v řadě`.","items":[]},{"heading":"E-mail odesílatele","text":"E-mail, ze kterého se budou zasílat informace o fakturách.","items":[]},{"heading":"Proměnné v e-mailu","text":"Proměnné v e-mailu budou před odesláním nahrazeny údaji z faktury. Jsou zapsány formou: \\"%Název_proměnné%\\".","items":["%number% - číslo faktury","%customer_name% - jméno / název odběratele","%amount% - celková částka faktury","%maturity% - splatnost","%maturityus% - splatnost v americkém formátu (např. 2026-12-24)","%vs% - variabilní symbol","%user% - jméno uživatele, který e-mail odeslal"]}]'],
        );
        $this->addSql(
            'INSERT INTO page_help (page_key, sections, updated_at, updated_by_name) VALUES (?, ?, NOW(), NULL)',
            ['Payments:InvoiceSequenceList:edit', '[{"heading":"Číselná řada","text":"Číslo faktury a VS se generují automaticky podle `Prefixu` a `Prvního čísla v řadě`.","items":[]},{"heading":"E-mail odesílatele","text":"E-mail, ze kterého se budou zasílat informace o fakturách.","items":[]},{"heading":"Proměnné v e-mailu","text":"Proměnné v e-mailu budou před odesláním nahrazeny údaji z faktury. Jsou zapsány formou: \\"%Název_proměnné%\\".","items":["%number% - číslo faktury","%customer_name% - jméno / název odběratele","%amount% - celková částka faktury","%maturity% - splatnost","%maturityus% - splatnost v americkém formátu (např. 2026-12-24)","%vs% - variabilní symbol","%user% - jméno uživatele, který e-mail odeslal"]}]'],
        );
        $this->addSql(
            'INSERT INTO page_help (page_key, sections, updated_at, updated_by_name) VALUES (?, ?, NOW(), NULL)',
            ['Payments:RegistrationCreateGroup:default', '[{"heading":"E-mail odesílatele","text":"E-mail, ze kterého se budou zasílat informace o platbách.","items":[]},{"heading":"Proměnné v e-mailu","text":"Proměnné v e-mailu budou před odesláním nahrazeny údaji z platby. Jsou zapsány formou: \\"%Název_proměnné%\\".","items":["%account% - celé číslo účtu i s předčíslím a kódem banky","%qrcode% - vygenerovaný obrázek QR kódu (nelze použít v předmětu)","%name% - název/účel platby","%groupname% - název platební skupiny","%amount% - částka","%maturity% - splatnost","%maturityus% - splatnost v americkém formátu (např. 2022-12-24)","%vs% - variabilní symbol","%ks% - konstantní symbol","%note% - poznámka","%user% - jméno uživatele, který e-mail odeslal"]}]'],
        );
        $this->addSql(
            'INSERT INTO page_help (page_key, sections, updated_at, updated_by_name) VALUES (?, ?, NOW(), NULL)',
            ['Settings:Invoices:default', '[{"heading":"Rok {$selectedYear}","text":"Roční nastavení je vedené samostatně pro každý rok a promítá se do nově vystavovaných faktur.","items":[]},{"heading":"Vazba na řady","text":"Na tohle nastavení navazují fakturační řady i samotné vystavení faktur v provozní části fakturace.","items":[]},{"heading":"Obrázky","text":"Logo: doporučený široký obrázek přibližně 500 x 140 px. Razítko s podpisem: doporučený široký obrázek přibližně 600 x 220 px. Větší obrázky se ve faktuře automaticky zmenší.","items":[]}]'],
        );
        $this->addSql(
            'INSERT INTO page_help (page_key, sections, updated_at, updated_by_name) VALUES (?, ?, NOW(), NULL)',
            ['Travel:Vehicle:detail', '[{"heading":"Archivace vozidla","text":"Archivované vozidlo zmizí ze seznamu vozidel i z nabídky při zakládání cestovního příkazu. Už vystavené příkazy zůstanou beze změny.","items":[]},{"heading":"Návrat mezi aktivní","text":"Vozidlo najdete v Archivu vozidel a tlačítkem Obnovit ho kdykoli vrátíte mezi aktivní. Archivace je vratná, smazání ne.","items":[]}]'],
        );
        $this->addSql(
            'INSERT INTO page_help (page_key, sections, updated_at, updated_by_name) VALUES (?, ?, NOW(), NULL)',
            ['Travel:VehicleList:archived', '[{"heading":"Co je archiv","text":"Vozidla vyřazená z provozu. Nejdou vybrat v novém cestovním příkazu, jejich historie ale zůstává dostupná.","items":[]},{"heading":"Obnovení vozidla","text":"Tlačítkem Obnovit se vozidlo vrátí mezi aktivní se všemi původními údaji i fotkami technického průkazu.","items":[]}]'],
        );
        $this->addSql(
            'INSERT INTO page_help (page_key, sections, updated_at, updated_by_name) VALUES (?, ?, NOW(), NULL)',
            ['Travel:VehicleList:default', '[{"heading":"Vozový park","text":"Seznam vozidel, ze kterých se vybírá při zakládání cestovního příkazu.","items":[]},{"heading":"Archivace","text":"Vozidlo, které už nepoužíváte, archivujte na jeho detailu. Zmizí z nabídek, cestovní příkazy zůstanou. Archivovaná vozidla najdete pod odkazem Archiv vozidel.","items":[]}]'],
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE page_help');
    }
}
