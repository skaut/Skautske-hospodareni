<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260827160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add contextual help for nine core pages';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'INSERT INTO page_help (page_key, sections, updated_at, updated_by_name) VALUES (?, ?, NOW(), NULL)',
            ['Travel:Contract:default', '[{"heading":"K čemu smlouva slouží","text":"Opravňuje jednotku proplácet konkrétnímu řidiči jízdy vlastním vozem. Při zakládání cestovního příkazu ji vyberete ze seznamu, příkaz ale jde vystavit i bez ní.","items":[]},{"heading":"Platnost tři roky","text":"Datum „Platná do“ se dopočítá samo, tři roky od začátku platnosti, a ručně ho změnit nelze. Po vypršení se smlouva u příkazu nabízí ve skupině „ukončené“.","items":[]},{"heading":"Tisk a smazání","text":"Ikona tiskárny otevře smlouvu k podpisu. Smazat ji jde jen na jejím detailu a jen tehdy, když na ni není navázaný žádný cestovní příkaz.","items":[]}]'],
        );
        $this->addSql(
            'INSERT INTO page_help (page_key, sections, updated_at, updated_by_name) VALUES (?, ?, NOW(), NULL)',
            ['Unit:Cashbook:default', '[{"heading":"Pokladna a banka odděleně","text":"Jsou to dvě samostatné evidence. Každá má vlastní číselnou řadu, vlastní prefix i vlastní exporty a doklad patří vždy jen do jedné z nich.","items":[]},{"heading":"Prefix a dogenerování čísel","text":"Prefix má nejvýš šest znaků. Tlačítko „Dogenerovat čísla dokladů“ je dostupné jen tehdy, když všechna stávající čísla obsahují pouze číslice.","items":[]},{"heading":"Zamčený doklad","text":"Zamčený doklad už nejde upravit ani smazat, jen zobrazit.","items":[]}]'],
        );
        $this->addSql(
            'INSERT INTO page_help (page_key, sections, updated_at, updated_by_name) VALUES (?, ?, NOW(), NULL)',
            ['Camps:Cashbook:default', '[{"heading":"Příjmy od účastníků","text":"Tlačítko „Načíst příjmy od účastníků“ vytvoří hromadný příjmový doklad z účastnických poplatků vedených u tábora.","items":[]},{"heading":"Kategorie nesmí být v mínusu","text":"SkautIS nepřijme zápornou částku v rozpočtové kategorii. Pokud by ji doklad stáhl pod nulu, neuloží se — nejdřív opravte vratky nebo příjmy účastníků.","items":[]},{"heading":"Výsledek hospodaření","text":"Spočítá se jen při zapnutém automatickém dopočítávání rozpočtu. Bez něj se místo částky zobrazí upozornění.","items":[]}]'],
        );
        $this->addSql(
            'INSERT INTO page_help (page_key, sections, updated_at, updated_by_name) VALUES (?, ?, NOW(), NULL)',
            ['Camps:Budget:default', '[{"heading":"Plán a skutečnost","text":"Rozpočet je plán vedený ve SkautISu. Skutečné částky se počítají z dokladů v evidenci plateb tábora.","items":[]},{"heading":"Nesoulad se SkautISem","text":"Když se součet dokladů liší od částek ve SkautISu, objeví se varování s odkazem „Aktualizovat data ve SkautISu“. Ten přepíše SkautIS podle evidence plateb, ne naopak.","items":[]},{"heading":"Bez oprávnění","text":"Pokud nemáte právo upravovat částky rozpočtu ve SkautISu, odkaz se nenabídne a nesoulad musí vyřešit někdo s vyšším oprávněním.","items":[]}]'],
        );
        $this->addSql(
            'INSERT INTO page_help (page_key, sections, updated_at, updated_by_name) VALUES (?, ?, NOW(), NULL)',
            ['Payments:Payment:default', '[{"heading":"Párování úhrad","text":"Funguje jen u skupiny s bankovním účtem, který má buď token do Fio, nebo nastavený zdroj GPC. Bez toho je tlačítko „Párovat úhrady“ nedostupné.","items":[]},{"heading":"Dogenerovat VS","text":"Doplní variabilní symboly navazující na poslední použitý ve skupině. Když poslední VS není platné číslo, tlačítko se nenabídne.","items":[]},{"heading":"Uzavření skupiny","text":"Uzavřená skupina zmizí z výchozího seznamu skupin. Zobrazíte ji přepnutím filtru na „Vše“.","items":[]}]'],
        );
        $this->addSql(
            'INSERT INTO page_help (page_key, sections, updated_at, updated_by_name) VALUES (?, ?, NOW(), NULL)',
            ['Payments:Repayment:default', '[{"heading":"Společné datum","text":"Datum se zadává jednou v hlavičce a použije se pro všechny vybrané vratky.","items":[]},{"heading":"Protiúčet","text":"Přebírá se z platby a před odesláním ho můžete u každého řádku přepsat.","items":[]}]'],
        );
        $this->addSql(
            'INSERT INTO page_help (page_key, sections, updated_at, updated_by_name) VALUES (?, ?, NOW(), NULL)',
            ['Events:Default:default', '[{"heading":"Data ze SkautISu","text":"Seznam přebírá akce ze SkautISu. Akce založená tam se objeví i tady a naopak.","items":[]},{"heading":"Prefix","text":"Prefix akce se používá pro číslování dokladů v její evidenci plateb.","items":[]}]'],
        );
        $this->addSql(
            'INSERT INTO page_help (page_key, sections, updated_at, updated_by_name) VALUES (?, ?, NOW(), NULL)',
            ['Camps:Default:default', '[{"heading":"Data ze SkautISu","text":"Seznam přebírá tábory ze SkautISu. Tábor založený tam se objeví i tady a naopak.","items":[]},{"heading":"Co hledání prohledává","text":"Vyhledávací pole projde název tábora i místo konání.","items":[]}]'],
        );
        $this->addSql(
            'INSERT INTO page_help (page_key, sections, updated_at, updated_by_name) VALUES (?, ?, NOW(), NULL)',
            ['Settings:BankAccounts:default', '[{"heading":"Import ze SkautISu","text":"Natáhne účty vedené u jednotky ve SkautISu. Účty, které už tu podle čísla jsou, přeskočí.","items":[]},{"heading":"Zdroj transakcí","text":"Účet bere pohyby buď z Fio API, kde potřebuje token, nebo z ručně nahraného souboru GPC.","items":[]},{"heading":"Podmínka párování","text":"Automaticky párovat úhrady lze jen u účtu s tokenem do Fio nebo se zdrojem GPC.","items":[]}]'],
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            'DELETE FROM page_help WHERE page_key IN (?)',
            [['Travel:Contract:default', 'Unit:Cashbook:default', 'Camps:Cashbook:default', 'Camps:Budget:default', 'Payments:Payment:default', 'Payments:Repayment:default', 'Events:Default:default', 'Camps:Default:default', 'Settings:BankAccounts:default']],
            [ArrayParameterType::STRING],
        );
    }
}
