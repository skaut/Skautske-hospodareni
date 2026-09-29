<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260924120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add contextual help for announcement pages';
    }

    public function up(Schema $schema): void
    {
        $help = [
            'Admin:Announcements:default' => '[{"heading":"Zveřejnění","text":"Nové oznámení se objeví na nástěnce ihned po uložení. Skryté a prošlé zprávy zůstávají v tomto přehledu.","items":[]},{"heading":"Vyhledávání","text":"Hledání prochází názvy a texty oznámení. Seznam můžete omezit také podle kategorie nebo stavu.","items":[]}]',
            'Admin:Announcements:create' => '[{"heading":"Platnost oznámení","text":"Datum konce zobrazování je nepovinné. Bez něj zpráva zůstane viditelná, dokud ji neskryjete nebo nesmažete.","items":[]},{"heading":"Zveřejnění","text":"Nové oznámení se po uložení ihned zobrazí na nástěnce.","items":[]}]',
            'Admin:Announcements:edit' => '[{"heading":"Úpravy","text":"Změny se po uložení ihned projeví uživatelům. Datum konce zobrazování můžete také odstranit.","items":[]},{"heading":"Skrytí","text":"Chcete-li zprávu dočasně stáhnout z nástěnky, použijte akci Skrýt v přehledu oznámení.","items":[]}]',
            'Announcements:default' => '[{"heading":"Aktuální zprávy","text":"Zobrazují se pouze zveřejněná oznámení, kterým neskončila platnost. Hledat můžete v názvu i textu.","items":[]}]',
            'Announcements:detail' => '[{"heading":"Platnost","text":"Tento detail je dostupný, dokud je oznámení zveřejněné a platné.","items":[]}]',
        ];

        foreach ($help as $pageKey => $sections) {
            $this->addSql(
                'INSERT IGNORE INTO page_help (page_key, sections, updated_at, updated_by_name) VALUES (?, ?, NOW(), NULL)',
                [$pageKey, $sections],
            );
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            'DELETE FROM page_help WHERE page_key IN (?)',
            [[
                'Admin:Announcements:default',
                'Admin:Announcements:create',
                'Admin:Announcements:edit',
                'Announcements:default',
                'Announcements:detail',
            ]],
            [ArrayParameterType::STRING],
        );
    }
}
