<?php

declare(strict_types=1);

namespace App\AccountancyModule\Factories;

use App\AccountancyModule\Components\DataGrid;
use Nette\Bridges\ApplicationLatte\Template;
use Ublaboo\DataGrid\Localization\SimpleTranslator;
use function assert;

class GridFactory
{
    private const TRANSLATIONS = [
        'ublaboo_datagrid.no_item_found_reset' => 'Nebyly nalezeny žádné položky. Zkuste zrušit filtry.',
        'ublaboo_datagrid.no_item_found' => 'Nebyly nalezeny žádné položky.',
        'ublaboo_datagrid.here' => 'Zde',
        'ublaboo_datagrid.items' => 'Položky',
        'ublaboo_datagrid.all' => 'vše',
        'ublaboo_datagrid.from' => 'od',
        'ublaboo_datagrid.reset_filter' => 'Zrušit filtry',
        'ublaboo_datagrid.group_actions' => 'Hromadné operace',
        'ublaboo_datagrid.show_all_columns' => 'Zobrazit všechny sloupce',
        'ublaboo_datagrid.hide_column' => 'Skrýt sloupec',
        'ublaboo_datagrid.action' => 'Akce',
        'ublaboo_datagrid.previous' => 'Předchozí',
        'ublaboo_datagrid.next' => 'Další',
        'ublaboo_datagrid.choose' => 'Vybrat',
        'ublaboo_datagrid.execute' => 'Provést',
        'ublaboo_datagrid.save' => 'Uložit',
        'ublaboo_datagrid.cancel' => 'Zrušit',
        'Name' => 'Jméno',
        'Inserted' => 'Vloženo',
    ];

    public function create() : DataGrid
    {
        $grid = new DataGrid();
        $grid->setDefaultPerPage(20);

        $translator = new SimpleTranslator(self::TRANSLATIONS);
        $grid->setTranslator($translator);

        return $grid;
    }

    /**
     * @param array<string, mixed> $templateParameters
     */
    public function createSimpleGrid(?string $templateFile = null, array $templateParameters = []) : DataGrid
    {
        $grid = new DataGrid();

        $grid->setColumnReset(false);
        $grid->setTranslator(new SimpleTranslator(self::TRANSLATIONS));
        $grid->setOuterFilterRendering(true);
        $grid->setCollapsibleOuterFilters(false);
        $grid->setPagination(false);
        $grid->setRememberState(false);
        $grid->setRefreshUrl(true);

        $grid->onAnchor[] = function () use ($grid, $templateFile, $templateParameters) : void {
            $template = $grid->getTemplate();
            assert($template instanceof Template);
            $baseTemplate = __DIR__ . '/../Components/templates/datagrid.latte';

            // This is variable with original layout in DataGrid 6.0+ (it replaces $original_template)
            $template->setParameters(['originalTemplate' => $baseTemplate]);
            $grid->setTemplateFile($templateFile ?? $baseTemplate);

            $template->setParameters($templateParameters);
        };

        $grid->onRedraw[] = function () use ($grid) : void {
            $presenter = $grid->presenter;

            if (! $presenter->isAjax()) {
                return;
            }

            $grid->redrawControl('global-actions');

            $presenter->payload->url     = $grid->link('this');
            $presenter->payload->postGet = true;
        };

        DataGrid::$icon_prefix = '';

        return $grid;
    }
}
