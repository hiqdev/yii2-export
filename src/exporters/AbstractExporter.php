<?php

namespace hiqdev\yii2\export\exporters;

use hiqdev\hiart\ActiveDataProvider;
use hiqdev\yii2\export\components\Exporter;
use hiqdev\yii2\export\models\BackgroundExport;
use hiqdev\yii2\export\models\SaveManager;
use hiqdev\yii2\menus\grid\MenuColumn;
use Yii;
use yii\db\ActiveRecord;
use yii\db\Query;
use yii\grid\DataColumn;
use yii\db\ActiveQueryInterface;
use yii\grid\ActionColumn;
use yii\grid\CheckboxColumn;
use yii\grid\Column;
use yii\grid\GridView;
use Box\Spout\Writer\WriterFactory;
use Box\Spout\Common\Exception\UnsupportedTypeException;

abstract class AbstractExporter implements ExporterInterface
{
    use GridViewTrait;

    public ?GridView $grid = null;
    public ?BackgroundExport $exportJob = null;
    public ?Exporter $exporter = null;
    public bool $exportFooter = true;
    public int $batchSize = 4_000;
    public ?string $filename = null;
    public string $target;
    public string $exportType;
    protected ?string $gridClassName = null;
    protected ActiveDataProvider $dataProvider;
    protected array $representationColumns = [];
    protected array $settings = [];

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
        if (empty($this->filename)) {
            $this->filename = 'report_' . time() . '.' . $this->exportType;
        }
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

    /**
     * @return array
     */
    public function getSettings(): array
    {
        return $this->settings;
    }

    /**
     * @param array $settings
     */
    public function setSettings(array $settings): void
    {
        $this->settings = $settings;
    }

    /**
     * Render file content
     *
     * @param SaveManager $saveManager
     * @throws UnsupportedTypeException
     */
    public function export(SaveManager $saveManager): void
    {
        $writer = WriterFactory::create($this->exportType);
        $writer = $this->applySettings($writer);
        $writer->openToFile($saveManager->getFilePath());

        //header
        $headerRow = $this->generateHeader();
        if (!empty($headerRow)) {
            $writer->addRow($headerRow);
        }

        //body
        $batches = $this->generateBody();
        foreach ($batches as $rows) {
            $writer->addRows($rows);
        }

        //footer
        $footerRow = $this->generateFooter();
        if (!empty($footerRow)) {
            $writer->addRow($footerRow);
        }

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

    /**
     * Fetch data from the data provider and create the rows array
     */
    protected function generateBody(): array
    {
        if (empty($this->grid->columns)) {
            return [];
        }

        $batch = [];
        $dp = $this->grid->dataProvider;

        if ($dp instanceof ActiveQueryInterface) {
            /** @var Query $query */
            $query = $dp->query;
            foreach ($query->batch($this->batchSize) as $models) {
                /**
                 * @var int $index
                 * @var ActiveRecord $model
                 */
                $rows = [];
                foreach ($models as $index => $model) {
                    $key = $model->getPrimaryKey();
                    $rows[] = $this->generateRow($model, $key, $index);
                    if ($this->exportJob && $this->exporter) {
                        $this->exportJob->increaseProgress();
                    }
                }
                $batch[] = [...$rows];
            }
        } else {
            $dp->pagination->setPageSize($this->batchSize);
            $dp->pagination->page = 0;
            $dp->refresh();
            $models = $dp->getModels();
            while (count($models) > 0) {
                $rows = [];
                foreach ($models as $index => $model) {
                    $rows[] = $this->generateRow($model, $model->id, $index);
                    if ($this->exportJob && $this->exporter) {
                        $this->exportJob->increaseProgress();
                    }
                }
                $batch[] = [...$rows];
                if ($dp->pagination) {
                    $dp->pagination->page++;
                    $dp->refresh();
                    unset($models);
                    $models = $dp->getModels();
                } else {
                    $models = [];
                }
            }
        }

        return $batch;
    }

    /**
     * Generate the row array
     *
     * @param $model
     * @param $key
     * @param $index
     * @return array
     */
    protected function generateRow($model, $key, $index): array
    {
        $row = [];
        foreach ($this->grid->columns as $column) {
            $value = $this->getColumnValue($model, $key, $index, $column);
            $row[] = $this->sanitizeRow($value);
        }

        return $row;
    }

    /**
     * Get the column generated value from the column
     *
     * @param $model
     * @param $key
     * @param $index
     * @param Column $column
     * @return string|null
     */
    protected function getColumnValue($model, $key, $index, Column $column): ?string
    {
        if ($column instanceof ActionColumn || $column instanceof CheckboxColumn) {
            return null;
        }
        $savedValue = $column->value;
        if (!empty($column->exportedValue)) {
            $column->value = $column->exportedValue;
        }
        if (method_exists($column, 'getDataCellValue')) {
            $cellValue = $column->getDataCellValue($model, $key, $index);
            $output = $this->grid->formatter->format($cellValue, $column->format);
        } else {
            $output = $column->renderDataCell($model, $key, $index);
        }
        $column->value = $savedValue;

        return $output;
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

    /**
     * Prevention execution code on an administrator’s machine in their user’s security context.
     *
     * @param string|null $value
     * @return string|null
     */
    protected function sanitizeRow(?string $value): ?string
    {
        if ($value) {
            $value = str_replace('&nbsp;', '', strip_tags($value));

            return ltrim($value, '=+');
        }

        return null;
    }

    protected function applySettings($writer)
    {
        return $writer;
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
}
