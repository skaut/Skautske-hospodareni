<?php

declare(strict_types=1);

namespace App\Components\Grids;

use App\Components\DataGrid;
use LogicException;
use Nette\Bridges\ApplicationLatte\DefaultTemplate;

class GridFactory
{
    public function create(): DataGrid
    {
        $grid = new DataGrid();
        $grid->setDefaultPerPage(20);
        DataGrid::$iconPrefix = '';

        return $grid;
    }

    /** @param array<string, mixed> $templateParameters */
    public function createSimpleGrid(?string $templateFile = null, array $templateParameters = []): DataGrid
    {
        $grid = new DataGrid();

        $grid->setColumnReset(false);
        $grid->setOuterFilterRendering(true);
        $grid->setCollapsibleOuterFilters(false);
        $grid->setPagination(false);
        $grid->setRememberState(false);
        $grid->setRefreshUrl(true);

        $grid->onAnchor[] = function () use ($grid, $templateFile, $templateParameters): void {
            $template = $grid->getTemplate();
            if (! $template instanceof DefaultTemplate) {
                throw new LogicException('Assertion failed.');
            }
            $baseTemplate = __DIR__.'/../../Components/templates/datagrid.latte';

            // This is variable with original layout in DataGrid 6.0+ (it replaces $original_template)
            $template->setParameters(['originalTemplate' => $baseTemplate]);
            $template->setParameters(['baseTemplate' => $baseTemplate]);
            $grid->setTemplateFile($templateFile ?? $baseTemplate);

            $template->setParameters($templateParameters);
        };

        $grid->onRedraw[] = function () use ($grid): void {
            $presenter = $grid->presenter;

            if (! $presenter->isAjax()) {
                return;
            }

            $grid->redrawControl('grid');

            $presenter->payload->url = $grid->link('this');
            $presenter->payload->postGet = true;
        };

        DataGrid::$iconPrefix = '';

        return $grid;
    }
}
