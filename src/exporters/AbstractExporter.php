<?php

declare(strict_types=1);


namespace hiqdev\yii2\export\exporters;

use Exception;
use hipanel\hiart\hiapi\HiapiConnectionInterface;
use hipanel\hiart\QueryBuilder;
use hiqdev\hiart\ActiveDataProvider;
use hiqdev\hiart\guzzle\Request;
use hiqdev\yii2\export\models\ExportJob;
use hiqdev\yii2\menus\grid\MenuColumn;
use NumberFormatter;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\WriterInterface;
use Yii;
use yii\grid\ActionColumn;
use yii\grid\CheckboxColumn;
use yii\grid\Column;
use yii\grid\DataColumn;
use yii\grid\GridView;
use yii\i18n\Formatter;

abstract class AbstractExporter implements ExporterInterface
{
    use GridViewTrait;

    public ?GridView $grid = null;
    public bool $exportFooter = true;
    public int $batchSize = 2000;
    public string $target;
    public ExportType $exportType;
    protected ?string $gridClassName = null;
    protected ActiveDataProvider $dataProvider;
    protected array $representationColumns = [];
    private ?ExportJob $exportJob = null;

    public function __sleep(): array
    {
        $attributes = array_keys(get_object_vars($this));
        unset($attributes['grid'], $attributes['dataProvider']);

        return $attributes;
    }

    public function __wakeup(): void
    {
        $this->initExportOptions();
    }

    public function setDataProvider(ActiveDataProvider $dataProvider): void
    {
        $this->dataProvider = $dataProvider;
        $this->dataProvider->enableSynchronousCount();
        $this->dataProvider->pagination->pageSize = $this->batchSize;
    }

    public function setRepresentationColumns(array $representationColumns): void
    {
        $this->representationColumns = $representationColumns;
    }

    public function setGridClassName(?string $gridClassName): void
    {
        $this->gridClassName = $gridClassName;
    }

    public function initExportOptions(): void
    {
        $grid = $this->createGridView();
        $columns = [];
        foreach ($grid->columns as $column) {
            if ($column instanceof CheckboxColumn || $column instanceof ActionColumn || $column instanceof MenuColumn) {
                continue;
            }
            if ($column instanceof \hiqdev\higrid\DataColumn && !empty($column->exportedColumns)) {
                $fakeGrid = Yii::createObject([
                    'class' => get_class($grid),
                    'dataProvider' => $grid->dataProvider,
                    'columns' => $column->exportedColumns,
                ]);
                foreach ($fakeGrid->columns as $exportedColumn) {
                    $columns[] = $exportedColumn;
                }
            } else {
                $columns[] = $column;
            }
        }
        $grid->columns = $columns;
        $this->grid = $grid;
    }

    abstract protected function getWriter(): ?WriterInterface;

    /**
     * Render file content
     */
    public function export(ExportJob $job): void
    {
        static::applyExportFormatting();

        $writer = $this->getWriter();
        $writer->openToFile($job->getSaver()->getFilePath());
        $rows = [];

        //header
        $headerRow = $this->generateHeader();
        if (!empty($headerRow)) {
            $row = Row::fromValues($headerRow);
            $rows[] = $row;
        }

        //body
        $batches = $this->generateBody();
        foreach ($batches as $batch) {
            foreach ($batch as $row) {
                $rows[] = Row::fromValues($row);
            }
        }

        //footer
        $footerRow = $this->generateFooter();
        if (!empty($footerRow)) {
            $row = Row::fromValues($footerRow);
            $rows[] = $row;
        }

        $writer->addRows($rows);

        $writer->close();
    }

    /**
     * Generate the row array
     *
     * @return array|void
     */
    protected function generateHeader()
    {
        if (empty($this->grid->columns)) {
            return;
        }

        $rows = [];
        foreach ($this->grid->columns as $column) {
            /** @var Column $column */
            if (($column instanceof DataColumn)) {
                $head = $this->getColumnHeader($column);
            } else {
                $head = $column->header;
            }
            $rows[] = $this->sanitizeRow($head);
        }

        return $rows;
    }

    protected function generateBody(): array
    {
        $connection = Yii::$container->get(HiapiConnectionInterface::class);
        if (empty($this->grid->columns)) {
            return [];
        }

        $batch = [];
        $dp = $this->grid->dataProvider;
        if (!$dp instanceof ActiveDataProvider) {
            throw new Exception('DataProvider must be an instance of ActiveDataProvider');
        }
        $totalCount = $dp->getTotalCount();
        $job = $this->exportJob;
        $dp->pagination->setPageSize($this->batchSize);
        $dp->pagination->page = 0;
        $pageCount = ceil($totalCount / $this->batchSize);

        $allRequests = [];
        foreach (range(1, $pageCount) as $page) {
            $allRequests[] = $this->composeRequest($connection, $dp);
            $dp->pagination->page = $page;
            $dp->refresh(keepTotalCount: true);
        }
        $responses = [];
        $chunks = array_chunk($allRequests, 5, true);
        $job->setTaskName(Yii::t('hiqdev.export', 'Getting data from the DB'))->setTotal(count($chunks))->setUnit('reqs')->commit();
        foreach ($chunks as $requests) {
            foreach ($connection->sendPool($requests) as $response) {
                $responses[] = $response;
            }
            $this->exportJob->increaseProgress()->commit();
        }
        $job->setTaskName(Yii::t('hiqdev.export', 'Generating a report'))->setTotal($dp->getTotalCount())->setUnit('rows')->commit();
        foreach ($responses as $response) {
            $data = $response->getData();
            $query = $response->getRequest()->getQuery();
            $rows = [];
            $models = $query->populate($data);
            foreach ($models as $index => $model) {
                $rows[] = $this->compileRow($model, $model->id, $index);
                $job->increaseProgress()->commit();
            }
            $batch[] = [...$rows];
        }

        return $batch;
    }

    public function composeRequest($connection, $dataProvider): Request
    {
        $query = $dataProvider->prepareQuery();
        $query->from = $query->modelClass::tableName();
        $query->addAction('search');
        $query->addOption('batch', true);

        return new Request(new QueryBuilder($connection), $query);
    }

    protected function compileRow($model, $key, $index): array
    {
        $row = [];
        foreach ($this->grid->columns as $column) {
            $value = $this->getColumnValue($model, $key, $index, $column);
            $row[] = is_string($value) ? $this->sanitizeRow($value) : $value;
        }

        return $row;
    }

    protected function getColumnValue($model, $key, $index, Column $column): ?string
    {
        if ($column instanceof ActionColumn || $column instanceof CheckboxColumn) {
            return null;
        }
        $savedValue = $column->value;
        if (!empty($column->exportedValue)) {
            $column->value = $column->exportedValue;
            $column->content = $column->exportedValue;
        }
        if (method_exists($column, 'getDataCellValue') && !$column->exportedValue) {
            $cellValue = $column->getDataCellValue($model, $key, $index);
            $output = $this->grid->formatter->format($cellValue, $column->format);
        } else {
            $output = $column->renderDataCell($model, $key, $index);
        }
        $column->value = $savedValue;

        return is_string($output) || $output === null ? $output : (string)$output;
    }

    /**
     * generate footer row array
     *
     * @return array|void
     */
    protected function generateFooter()
    {
        if (!$this->exportFooter) {
            return;
        }

        if (empty($this->grid->columns)) {
            return;
        }

        $rows = [];
        foreach ($this->grid->columns as $column) {
            /** @var Column $column */
            $rows[] = $column->footer !== '' ? $this->sanitizeRow($column->footer) : '';
        }

        return $rows;
    }

    protected function sanitizeRow(?string $value): ?string
    {
        if ($value) {
            $value = str_replace('&nbsp;', '', strip_tags($value));
            $value = trim(preg_replace('/\s\s+/', '', $value));

            return ltrim($value, '=+');
        }

        return null;
    }

    public function setExportJob(?ExportJob $exportJob): void
    {
        $this->exportJob = $exportJob;
    }

    private function createGridView(): GridView
    {
        $dataProvider = $this->dataProvider;
        /** @var GridView $grid */
        $grid = Yii::createObject([
            'class' => $this->gridClassName,
            'dataProvider' => $dataProvider,
            'columns' => $this->representationColumns,
        ]);
        $grid->dataColumnClass = DataColumn::class;

        return $grid;
    }

    public static function applyExportFormatting(): Formatter
    {
        $formatter = Yii::$app->formatter;
        $formatter->currencyDecimalSeparator = ',';
        $formatter->decimalSeparator = ',';
        $formatter->thousandSeparator = '';
        $formatter->numberFormatterOptions = [
            NumberFormatter::MIN_FRACTION_DIGITS => 2,
            NumberFormatter::MAX_FRACTION_DIGITS => 2,
        ];

        return $formatter;
    }
}
