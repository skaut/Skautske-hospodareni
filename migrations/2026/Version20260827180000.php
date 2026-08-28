<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260827180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add help for nine more pages and normalise existing headings to noun phrases';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'INSERT INTO page_help (page_key, sections, updated_at, updated_by_name) VALUES (?, ?, NOW(), NULL)',
            ['Events:Cashbook:default', '[{"heading":"Příjmy od účastníků","text":"Tlačítko „Načíst příjmy od účastníků“ vytvoří hromadný příjmový doklad z účastnických poplatků vedených u akce.","items":[]},{"heading":"Záporný zůstatek","text":"Když evidence spadne do minusu, objeví se varování. Nejčastější příčinou je chybějící import příjmů od účastníků.","items":[]},{"heading":"Pokladna a banka","text":"Jsou to dvě samostatné evidence. Každá má vlastní číselnou řadu, vlastní prefix i vlastní exporty.","items":[]}]'],
        );
        $this->addSql(
            'INSERT INTO page_help (page_key, sections, updated_at, updated_by_name) VALUES (?, ?, NOW(), NULL)',
            ['Unit:Budget:default', '[{"heading":"Roční rozpočet","text":"Plán se zadává zvlášť pro každý rok a rok přepínáte v hlavičce stránky.","items":[]},{"heading":"Předpoklad a skutečnost","text":"Sloupec Předpoklad je plánovaná částka. Skutečné čerpání najdete v pokladní knize jednotky.","items":[]}]'],
        );
        $this->addSql(
            'INSERT INTO page_help (page_key, sections, updated_at, updated_by_name) VALUES (?, ?, NOW(), NULL)',
            ['Travel:Command:detail', '[{"heading":"Uzavřený příkaz","text":"Po uzavření už nejde upravit ani příkaz, ani jeho jednotlivé cesty. Znovu ho otevřete tlačítkem „Otevřít“.","items":[]},{"heading":"Duplikace cesty","text":"U každé cesty jsou ikony pro duplikaci a pro přidání zpáteční cesty, takže ji nemusíte vyplňovat celou znovu.","items":[]},{"heading":"Oprávnění k úpravám","text":"Příkaz může upravit ten, kdo ho založil, nebo kdokoli s právem editace v jednotce příkazu.","items":[]}]'],
        );
        $this->addSql(
            'INSERT INTO page_help (page_key, sections, updated_at, updated_by_name) VALUES (?, ?, NOW(), NULL)',
            ['Travel:Vehicle:new', '[{"heading":"Harmonizovaná spotřeba","text":"Použije se údaj pro kombinovaný provoz podle norem EU. U aut vyrobených od roku 1999 je to třetí údaj spotřeby v technickém průkazu, u starších aritmetický průměr uvedených hodnot.","items":[]},{"heading":"Použití vozidla","text":"Po založení se vozidlo nabízí při zakládání cestovního příkazu. Až ho přestanete používat, archivujte ho na jeho detailu.","items":[]}]'],
        );
        $this->addSql(
            'INSERT INTO page_help (page_key, sections, updated_at, updated_by_name) VALUES (?, ?, NOW(), NULL)',
            ['Travel:Default:default', '[{"heading":"Předpoklady pro příkaz","text":"U jízdy vlastním vozem je v příkazu potřeba vybrat vozidlo. Vozidla i smlouvy s řidiči zakládáte v záložkách Vozidla a Smlouvy.","items":[]},{"heading":"Rozsah hledání","text":"Vyhledávací pole projde účel cesty a jméno cestujícího.","items":[]}]'],
        );
        $this->addSql(
            'INSERT INTO page_help (page_key, sections, updated_at, updated_by_name) VALUES (?, ?, NOW(), NULL)',
            ['Settings:Mails:default', '[{"heading":"Propojení přes Google","text":"Odesílací e-mail se připojuje přes účet Google. Z této adresy pak aplikace rozesílá zprávy o platbách.","items":[]},{"heading":"Sdílení mezi jednotkami","text":"Každý propojený účet jde použít jako odesílatele tam, kde k němu má vybraná jednotka přístup.","items":[]}]'],
        );
        $this->addSql(
            'INSERT INTO page_help (page_key, sections, updated_at, updated_by_name) VALUES (?, ?, NOW(), NULL)',
            ['Payments:Payment:massAdd', '[{"heading":"Předvybrané e-maily","text":"U každé osoby je předvybraná jen hlavní adresa. Adresy rodičů a další kontakty je potřeba zaškrtnout ručně.","items":[]},{"heading":"Hromadný výběr","text":"Tlačítky nad seznamem přidáte nebo odeberete celý typ adresy najednou.","items":[]}]'],
        );
        $this->addSql(
            'INSERT INTO page_help (page_key, sections, updated_at, updated_by_name) VALUES (?, ?, NOW(), NULL)',
            ['Settings:BankAccounts:detail', '[{"heading":"Zdroj transakcí","text":"Účet s tokenem do Fio si transakce stahuje sám. U ostatních účtů je nahrajete tlačítkem „Importovat GPC“.","items":[]},{"heading":"Rozsah přehledu","text":"Seznam ukazuje posledních 60 dní včetně kandidátů na ruční párování. Celou historii zobrazíte přepínačem nad tabulkou.","items":[]}]'],
        );
        $this->addSql(
            'INSERT INTO page_help (page_key, sections, updated_at, updated_by_name) VALUES (?, ?, NOW(), NULL)',
            ['Events:Event:default', '[{"heading":"Uzavření akce","text":"Akci lze uzavřít až poté, co je v sekci Vedení akce vyplněný vedoucí. Bez něj se uzavření odmítne.","items":[]},{"heading":"Připomenutí uzavření","text":"Tlačítko „Uzavřít“ se zvýrazní, jakmile od konce akce uplyne 14 dní.","items":[]}]'],
        );

        // Headings are short, formal noun phrases. The invoice settings heading also
        // still carried an unresolved Latte variable from the first seed.
        $this->addSql(
            'UPDATE page_help SET sections = ? WHERE page_key = ?',
            ['[{"heading":"Roční nastavení","text":"Roční nastavení je vedené samostatně pro každý rok a promítá se do nově vystavovaných faktur.","items":[]},{"heading":"Vazba na řady","text":"Na tohle nastavení navazují fakturační řady i samotné vystavení faktur v provozní části fakturace.","items":[]},{"heading":"Obrázky","text":"Logo: doporučený široký obrázek přibližně 500 x 140 px. Razítko s podpisem: doporučený široký obrázek přibližně 600 x 220 px. Větší obrázky se ve faktuře automaticky zmenší.","items":[]}]', 'Settings:Invoices:default'],
        );
        $this->addSql(
            'UPDATE page_help SET sections = ? WHERE page_key = ?',
            ['[{"heading":"Účel smlouvy","text":"Opravňuje jednotku proplácet konkrétnímu řidiči jízdy vlastním vozem. Při zakládání cestovního příkazu ji vyberete ze seznamu, příkaz ale jde vystavit i bez ní.","items":[]},{"heading":"Platnost tři roky","text":"Datum „Platná do“ se dopočítá samo, tři roky od začátku platnosti, a ručně ho změnit nelze. Po vypršení se smlouva u příkazu nabízí ve skupině „ukončené“.","items":[]},{"heading":"Tisk a smazání","text":"Ikona tiskárny otevře smlouvu k podpisu. Smazat ji jde jen na jejím detailu a jen tehdy, když na ni není navázaný žádný cestovní příkaz.","items":[]}]', 'Travel:Contract:default'],
        );
        $this->addSql(
            'UPDATE page_help SET sections = ? WHERE page_key = ?',
            ['[{"heading":"Příjmy od účastníků","text":"Tlačítko „Načíst příjmy od účastníků“ vytvoří hromadný příjmový doklad z účastnických poplatků vedených u tábora.","items":[]},{"heading":"Záporné kategorie","text":"SkautIS nepřijme zápornou částku v rozpočtové kategorii. Pokud by ji doklad stáhl pod nulu, neuloží se — nejdřív opravte vratky nebo příjmy účastníků.","items":[]},{"heading":"Výsledek hospodaření","text":"Spočítá se jen při zapnutém automatickém dopočítávání rozpočtu. Bez něj se místo částky zobrazí upozornění.","items":[]}]', 'Camps:Cashbook:default'],
        );
        $this->addSql(
            'UPDATE page_help SET sections = ? WHERE page_key = ?',
            ['[{"heading":"Plán a skutečnost","text":"Rozpočet je plán vedený ve SkautISu. Skutečné částky se počítají z dokladů v evidenci plateb tábora.","items":[]},{"heading":"Nesoulad se SkautISem","text":"Když se součet dokladů liší od částek ve SkautISu, objeví se varování s odkazem „Aktualizovat data ve SkautISu“. Ten přepíše SkautIS podle evidence plateb, ne naopak.","items":[]},{"heading":"Oprávnění","text":"Pokud nemáte právo upravovat částky rozpočtu ve SkautISu, odkaz se nenabídne a nesoulad musí vyřešit někdo s vyšším oprávněním.","items":[]}]', 'Camps:Budget:default'],
        );
        $this->addSql(
            'UPDATE page_help SET sections = ? WHERE page_key = ?',
            ['[{"heading":"Párování úhrad","text":"Funguje jen u skupiny s bankovním účtem, který má buď token do Fio, nebo nastavený zdroj GPC. Bez toho je tlačítko „Párovat úhrady“ nedostupné.","items":[]},{"heading":"Generování variabilních symbolů","text":"Doplní variabilní symboly navazující na poslední použitý ve skupině. Když poslední VS není platné číslo, tlačítko se nenabídne.","items":[]},{"heading":"Uzavření skupiny","text":"Uzavřená skupina zmizí z výchozího seznamu skupin. Zobrazíte ji přepnutím filtru na „Vše“.","items":[]}]', 'Payments:Payment:default'],
        );
        $this->addSql(
            'UPDATE page_help SET sections = ? WHERE page_key = ?',
            ['[{"heading":"Data ze SkautISu","text":"Seznam přebírá tábory ze SkautISu. Tábor založený tam se objeví i tady a naopak.","items":[]},{"heading":"Rozsah hledání","text":"Vyhledávací pole projde název tábora i místo konání.","items":[]}]', 'Camps:Default:default'],
        );
        $this->addSql(
            'UPDATE page_help SET sections = ? WHERE page_key = ?',
            ['[{"heading":"Pokladna a banka","text":"Jsou to dvě samostatné evidence. Každá má vlastní číselnou řadu, vlastní prefix i vlastní exporty a doklad patří vždy jen do jedné z nich.","items":[]},{"heading":"Prefix a číslování dokladů","text":"Prefix má nejvýš šest znaků. Tlačítko „Dogenerovat čísla dokladů“ je dostupné jen tehdy, když všechna stávající čísla obsahují pouze číslice.","items":[]},{"heading":"Zamčený doklad","text":"Zamčený doklad už nejde upravit ani smazat, jen zobrazit.","items":[]}]', 'Unit:Cashbook:default'],
        );
        $this->addSql(
            'UPDATE page_help SET sections = ? WHERE page_key = ?',
            ['[{"heading":"Archiv vozidel","text":"Vozidla vyřazená z provozu. Nejdou vybrat v novém cestovním příkazu, jejich historie ale zůstává dostupná.","items":[]},{"heading":"Obnovení vozidla","text":"Tlačítkem Obnovit se vozidlo vrátí mezi aktivní se všemi původními údaji i fotkami technického průkazu.","items":[]}]', 'Travel:VehicleList:archived'],
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            'DELETE FROM page_help WHERE page_key IN (?)',
            [['Events:Cashbook:default', 'Unit:Budget:default', 'Travel:Command:detail', 'Travel:Vehicle:new', 'Travel:Default:default', 'Settings:Mails:default', 'Payments:Payment:massAdd', 'Settings:BankAccounts:detail', 'Events:Event:default']],
            [ArrayParameterType::STRING],
        );

        $this->addSql(
            'UPDATE page_help SET sections = ? WHERE page_key = ?',
            ['[{"heading":"Rok {$selectedYear}","text":"Roční nastavení je vedené samostatně pro každý rok a promítá se do nově vystavovaných faktur.","items":[]},{"heading":"Vazba na řady","text":"Na tohle nastavení navazují fakturační řady i samotné vystavení faktur v provozní části fakturace.","items":[]},{"heading":"Obrázky","text":"Logo: doporučený široký obrázek přibližně 500 x 140 px. Razítko s podpisem: doporučený široký obrázek přibližně 600 x 220 px. Větší obrázky se ve faktuře automaticky zmenší.","items":[]}]', 'Settings:Invoices:default'],
        );
        $this->addSql(
            'UPDATE page_help SET sections = ? WHERE page_key = ?',
            ['[{"heading":"K čemu smlouva slouží","text":"Opravňuje jednotku proplácet konkrétnímu řidiči jízdy vlastním vozem. Při zakládání cestovního příkazu ji vyberete ze seznamu, příkaz ale jde vystavit i bez ní.","items":[]},{"heading":"Platnost tři roky","text":"Datum „Platná do“ se dopočítá samo, tři roky od začátku platnosti, a ručně ho změnit nelze. Po vypršení se smlouva u příkazu nabízí ve skupině „ukončené“.","items":[]},{"heading":"Tisk a smazání","text":"Ikona tiskárny otevře smlouvu k podpisu. Smazat ji jde jen na jejím detailu a jen tehdy, když na ni není navázaný žádný cestovní příkaz.","items":[]}]', 'Travel:Contract:default'],
        );
        $this->addSql(
            'UPDATE page_help SET sections = ? WHERE page_key = ?',
            ['[{"heading":"Příjmy od účastníků","text":"Tlačítko „Načíst příjmy od účastníků“ vytvoří hromadný příjmový doklad z účastnických poplatků vedených u tábora.","items":[]},{"heading":"Kategorie nesmí být v mínusu","text":"SkautIS nepřijme zápornou částku v rozpočtové kategorii. Pokud by ji doklad stáhl pod nulu, neuloží se — nejdřív opravte vratky nebo příjmy účastníků.","items":[]},{"heading":"Výsledek hospodaření","text":"Spočítá se jen při zapnutém automatickém dopočítávání rozpočtu. Bez něj se místo částky zobrazí upozornění.","items":[]}]', 'Camps:Cashbook:default'],
        );
        $this->addSql(
            'UPDATE page_help SET sections = ? WHERE page_key = ?',
            ['[{"heading":"Plán a skutečnost","text":"Rozpočet je plán vedený ve SkautISu. Skutečné částky se počítají z dokladů v evidenci plateb tábora.","items":[]},{"heading":"Nesoulad se SkautISem","text":"Když se součet dokladů liší od částek ve SkautISu, objeví se varování s odkazem „Aktualizovat data ve SkautISu“. Ten přepíše SkautIS podle evidence plateb, ne naopak.","items":[]},{"heading":"Bez oprávnění","text":"Pokud nemáte právo upravovat částky rozpočtu ve SkautISu, odkaz se nenabídne a nesoulad musí vyřešit někdo s vyšším oprávněním.","items":[]}]', 'Camps:Budget:default'],
        );
        $this->addSql(
            'UPDATE page_help SET sections = ? WHERE page_key = ?',
            ['[{"heading":"Párování úhrad","text":"Funguje jen u skupiny s bankovním účtem, který má buď token do Fio, nebo nastavený zdroj GPC. Bez toho je tlačítko „Párovat úhrady“ nedostupné.","items":[]},{"heading":"Dogenerovat VS","text":"Doplní variabilní symboly navazující na poslední použitý ve skupině. Když poslední VS není platné číslo, tlačítko se nenabídne.","items":[]},{"heading":"Uzavření skupiny","text":"Uzavřená skupina zmizí z výchozího seznamu skupin. Zobrazíte ji přepnutím filtru na „Vše“.","items":[]}]', 'Payments:Payment:default'],
        );
        $this->addSql(
            'UPDATE page_help SET sections = ? WHERE page_key = ?',
            ['[{"heading":"Data ze SkautISu","text":"Seznam přebírá tábory ze SkautISu. Tábor založený tam se objeví i tady a naopak.","items":[]},{"heading":"Co hledání prohledává","text":"Vyhledávací pole projde název tábora i místo konání.","items":[]}]', 'Camps:Default:default'],
        );
        $this->addSql(
            'UPDATE page_help SET sections = ? WHERE page_key = ?',
            ['[{"heading":"Pokladna a banka odděleně","text":"Jsou to dvě samostatné evidence. Každá má vlastní číselnou řadu, vlastní prefix i vlastní exporty a doklad patří vždy jen do jedné z nich.","items":[]},{"heading":"Prefix a dogenerování čísel","text":"Prefix má nejvýš šest znaků. Tlačítko „Dogenerovat čísla dokladů“ je dostupné jen tehdy, když všechna stávající čísla obsahují pouze číslice.","items":[]},{"heading":"Zamčený doklad","text":"Zamčený doklad už nejde upravit ani smazat, jen zobrazit.","items":[]}]', 'Unit:Cashbook:default'],
        );
        $this->addSql(
            'UPDATE page_help SET sections = ? WHERE page_key = ?',
            ['[{"heading":"Co je archiv","text":"Vozidla vyřazená z provozu. Nejdou vybrat v novém cestovním příkazu, jejich historie ale zůstává dostupná.","items":[]},{"heading":"Obnovení vozidla","text":"Tlačítkem Obnovit se vozidlo vrátí mezi aktivní se všemi původními údaji i fotkami technického průkazu.","items":[]}]', 'Travel:VehicleList:archived'],
        );
    }
}
