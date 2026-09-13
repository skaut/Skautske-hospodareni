<?php

declare(strict_types=1);

namespace App\Components\Grids;

use App\Components\DataGrid;
use LogicException;
use Nette\Bridges\ApplicationLatte\DefaultTemplate;

class GridFactory
{
    /** @param array<string, mixed> $templateParameters */
    public function create(?string $templateFile = null, array $templateParameters = []): DataGrid
    {
        $grid = new DataGrid();
        $grid->setDefaultPerPage(20);
        if ($templateFile !== null) {
            $this->configureTemplate($grid, $templateFile, $templateParameters);
        }

        $this->configureAjaxRedraw($grid);

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

        $this->configureTemplate($grid, $templateFile, $templateParameters);
        $this->configureAjaxRedraw($grid);

        DataGrid::$iconPrefix = '';

        return $grid;
    }

    /**
     * Keeps the grid snippet and the browser URL in sync when filtering, sorting
     * or paging happens over AJAX.
     */
    private function configureAjaxRedraw(DataGrid $grid): void
    {
        $grid->onRedraw[] = function () use ($grid): void {
            $presenter = $grid->getPresenter();

            if ($presenter === null || ! $presenter->isAjax()) {
                return;
            }

            $grid->redrawControl('grid');

            $presenter->payload->url = $grid->link('this');
            $presenter->payload->postGet = true;
        };
    }

    /** @param array<string, mixed> $templateParameters */
    private function configureTemplate(DataGrid $grid, ?string $templateFile, array $templateParameters): void
    {
        $grid->onAnchor[] = function () use ($grid, $templateFile, $templateParameters): void {
            $template = $grid->getTemplate();
            if (! $template instanceof DefaultTemplate) {
                throw new LogicException('Assertion failed.');
            }
            $baseTemplate = __DIR__.'/../../Components/templates/datagrid.latte';

            // $originalTemplate (vendor default 7.x) nastavuje sám grid v render(); náš datagrid.latte
            // ho přes {extends $originalTemplate} rozšiřuje. Konkrétní gridy dědí přes $baseTemplate.
            $template->setParameters(['baseTemplate' => $baseTemplate]);
            $grid->setTemplateFile($templateFile ?? $baseTemplate);

            $template->setParameters($templateParameters);
        };
    }
}
