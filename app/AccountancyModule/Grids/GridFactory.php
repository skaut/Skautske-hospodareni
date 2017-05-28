<?php

namespace App\AccountancyModule\Factories;

use Ublaboo\DataGrid\DataGrid;

class GridFactory
{

    public function create(): DataGrid
    {
        $grid = new DataGrid();
        $grid->setDefaultPerPage(20);

        $translator = new \Ublaboo\DataGrid\Localization\SimpleTranslator([
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
            'Inserted' => 'Vloženo'
        ]);
        $grid->setTranslator($translator);

        return $grid;
    }

}
