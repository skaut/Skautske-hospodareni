<?php

declare(strict_types=1);

namespace App\Components;

use LogicException;
use Ublaboo\DataGrid\Column\Action;
use Ublaboo\DataGrid\Filter\FilterSelect;
use Ublaboo\DataGrid\Localization\SimpleTranslator;

use function array_map;
use function array_reverse;
use function date;
use function is_array;
use function iterator_to_array;
use function range;
use function Safe\array_combine;

class DataGrid extends \Ublaboo\DataGrid\DataGrid
{
    public const OPTION_ALL = 'all';

    public const SORT_ASC = 'ASC';
    public const SORT_DESC = 'DESC';

    public function __construct()
    {
        parent::__construct();

        Action::$dataConfirmAttributeName = 'data-confirm';
        $this->onRender[] = function (): void {
            // disable autocomplete - issue #1443
            $this['filter']->getElementPrototype()->setAttribute('autocomplete', 'off');
        };

        $translator = new SimpleTranslator([
            'ublaboo_datagrid.no_item_found_reset' => 'Nenalezeny žádné záznamy. Můžete zrušit filtr',
            'ublaboo_datagrid.no_item_found' => 'Nenalezeny žádné záznamy.',
            'ublaboo_datagrid.here' => 'zde',
            'ublaboo_datagrid.items' => 'Položky',
            'ublaboo_datagrid.all' => 'vše',
            'ublaboo_datagrid.from' => 'od',
            'ublaboo_datagrid.reset_filter' => 'Zrušit filtr',
            'ublaboo_datagrid.group_actions' => 'Hromadné akce',
            'ublaboo_datagrid.show' => 'Zobrazit',
            'ublaboo_datagrid.add' => 'Přidat',
            'ublaboo_datagrid.edit' => 'Upravit',
            'ublaboo_datagrid.show_all_columns' => 'Zobrazit všechny sloupce',
            'ublaboo_datagrid.show_default_columns' => 'Zobrazit výchozí sloupce',
            'ublaboo_datagrid.hide_column' => 'Skrýt sloupec',
            'ublaboo_datagrid.action' => 'Akce',
            'ublaboo_datagrid.previous' => 'Předchozí',
            'ublaboo_datagrid.next' => 'Další',
            'ublaboo_datagrid.choose' => 'Vybrat',
            'ublaboo_datagrid.choose_input_required' => 'Text hromadné akce nesmí být prázdný',
            'ublaboo_datagrid.execute' => 'Provést',
            'ublaboo_datagrid.save' => 'Uložit',
            'ublaboo_datagrid.cancel' => 'Zrušit',
            'ublaboo_datagrid.multiselect_choose' => 'Vybrat',
            'ublaboo_datagrid.multiselect_selected' => '{0} vybráno',
            'ublaboo_datagrid.filter_submit_button' => 'Filtrovat',
            'ublaboo_datagrid.show_filter' => 'Zobrazit filtr',
            'ublaboo_datagrid.per_page_submit' => 'Změnit',
        ]);

        $this->setTranslator($translator);
    }

    /**
     * Forces datagrid to filter and sort data source and returns inner data.
     *
     * @return mixed[]
     */
    public function getFilteredAndSortedData(): array
    {
        $data = ($this->dataModel ?? throw new LogicException('Data source not set.'))->filterData(
            $this->getPaginator(),
            $this->createSorting($this->sort, $this->sortCallback),
            $this->assembleFilters(),
        );

        return is_array($data) ? $data : iterator_to_array($data);
    }

    public function addYearFilter(string $name, string $label): FilterSelect
    {
        return $this->addFilterSelect($name, $label, $this->getYearOptions(), 'year');
    }

    /** @return array<string, string> */
    private function getYearOptions(): array
    {
        $years = array_map(
            function (int $year): string {
                return (string) $year;
            },
            array_reverse(range(2012, (int) date('Y') + 1)),
        );

        return [self::OPTION_ALL => 'Všechny'] + array_combine($years, $years);
    }
}
