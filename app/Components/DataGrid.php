<?php

declare(strict_types=1);

namespace App\Components;

use Contributte\Datagrid\Column\Action;
use Contributte\Datagrid\Filter\FilterSelect;
use Contributte\Datagrid\Localization\SimpleTranslator;
use LogicException;

use function array_map;
use function array_reverse;
use function date;
use function is_array;
use function iterator_to_array;
use function range;
use function Safe\array_combine;

class DataGrid extends \Contributte\Datagrid\Datagrid
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
            'contributte_datagrid.no_item_found_reset' => 'Nenalezeny žádné záznamy. Můžete zrušit filtr',
            'contributte_datagrid.no_item_found' => 'Nenalezeny žádné záznamy.',
            'contributte_datagrid.here' => 'zde',
            'contributte_datagrid.items' => 'Položky',
            'contributte_datagrid.all' => 'vše',
            'contributte_datagrid.from' => 'od',
            'contributte_datagrid.reset_filter' => 'Zrušit filtr',
            'contributte_datagrid.group_actions' => 'Hromadné akce',
            'contributte_datagrid.show' => 'Zobrazit',
            'contributte_datagrid.add' => 'Přidat',
            'contributte_datagrid.edit' => 'Upravit',
            'contributte_datagrid.show_all_columns' => 'Zobrazit všechny sloupce',
            'contributte_datagrid.show_default_columns' => 'Zobrazit výchozí sloupce',
            'contributte_datagrid.hide_column' => 'Skrýt sloupec',
            'contributte_datagrid.action' => 'Akce',
            'contributte_datagrid.previous' => 'Předchozí',
            'contributte_datagrid.next' => 'Další',
            'contributte_datagrid.choose' => 'Vybrat',
            'contributte_datagrid.choose_input_required' => 'Text hromadné akce nesmí být prázdný',
            'contributte_datagrid.execute' => 'Provést',
            'contributte_datagrid.save' => 'Uložit',
            'contributte_datagrid.cancel' => 'Zrušit',
            'contributte_datagrid.multiselect_choose' => 'Vybrat',
            'contributte_datagrid.multiselect_selected' => '{0} vybráno',
            'contributte_datagrid.filter_submit_button' => 'Filtrovat',
            'contributte_datagrid.show_filter' => 'Zobrazit filtr',
            'contributte_datagrid.per_page_submit' => 'Změnit',
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
