<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261017120000 extends AbstractMigration
{
    private const PageKey = 'Travel:Contract:default';

    private const PrintIconSections = '[{"heading":"Účel smlouvy","text":"Opravňuje jednotku proplácet konkrétnímu řidiči jízdy vlastním vozem. Při zakládání cestovního příkazu ji vyberete ze seznamu, příkaz ale jde vystavit i bez ní.","items":[]},{"heading":"Platnost tři roky","text":"Datum „Platná do“ se dopočítá samo, tři roky od začátku platnosti, a ručně ho změnit nelze. Po vypršení se smlouva u příkazu nabízí ve skupině „ukončené“.","items":[]},{"heading":"Tisk a smazání","text":"Ikona tiskárny otevře smlouvu k podpisu. Smazat ji jde jen na jejím detailu a jen tehdy, když na ni není navázaný žádný cestovní příkaz.","items":[]}]';

    private const DownloadSections = '[{"heading":"Účel smlouvy","text":"Opravňuje jednotku proplácet konkrétnímu řidiči jízdy vlastním vozem. Při zakládání cestovního příkazu ji vyberete ze seznamu, příkaz ale jde vystavit i bez ní.","items":[]},{"heading":"Platnost tři roky","text":"Datum „Platná do“ se dopočítá samo, tři roky od začátku platnosti, a ručně ho změnit nelze. Po vypršení se smlouva u příkazu nabízí ve skupině „ukončené“.","items":[]},{"heading":"Stažení a smazání","text":"Tlačítko „Stáhnout PDF“ (v seznamu ikona stažení) uloží smlouvu k podpisu, šipka vedle něj nabídne „Otevřít v nové kartě“. Smazat smlouvu jde jen na jejím detailu a jen tehdy, když na ni není navázaný žádný cestovní příkaz.","items":[]}]';

    public function getDescription(): string
    {
        return 'Describe contract PDF download and open actions in contract list help';
    }

    public function up(Schema $schema): void
    {
        // Mění se jen původní text – nápovědu upravenou v administraci migrace nepřepíše.
        $this->addSql(
            'UPDATE page_help SET sections = ?, updated_at = NOW() WHERE page_key = ? AND sections = CAST(? AS JSON)',
            [self::DownloadSections, self::PageKey, self::PrintIconSections],
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            'UPDATE page_help SET sections = ?, updated_at = NOW() WHERE page_key = ? AND sections = CAST(? AS JSON)',
            [self::PrintIconSections, self::PageKey, self::DownloadSections],
        );
    }
}
