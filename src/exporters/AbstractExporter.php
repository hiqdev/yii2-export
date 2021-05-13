<?php

namespace hiqdev\yii2\export\exporters;

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
use Box\Spout\Common\Exception\IOException;
use Box\Spout\Common\Exception\UnsupportedTypeException;
use Box\Spout\Writer\Exception\WriterNotOpenedException;

abstract class AbstractExporter
{
    use GridViewTrait;

    public GridView $grid;

    /**
     * @var bool whether to export footer or not
     */
    public bool $exportFooter = true;

    /**
     * @var int batch size to fetch the data provider
     */
    public int $batchSize = 2000;

    /**
     * @var string filename without extension
     */
    public string $filename;

    /**
     * @see ExportMenu target consts
     * @var string how the page will delivery the report
     */
    public string $target;


    /**
     * Setting for exported file
     *
     * @var array
     */
    protected array $settings = [];

    /**
     * Export type
     *
     * @var string
     */
    protected string $exportType;

    /**
     * Init
     *
     * @param $grid
     */
    public function initExportOptions(GridView $grid): void
    {
        if (empty($this->filename)) {
            $this->filename = 'report_' . time();
        }
        $columns = [];
        foreach ($grid->columns as $idx => $column) {
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
        foreach ($this->grid->columns as $attribute => $column) {
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

        $rows = [];
        $dp = $this->grid->dataProvider;

        if ($dp instanceof ActiveQueryInterface) {
            /** @var Query $query */
            $query = $dp->query;
            foreach ($query->batch($this->batchSize) as $models) {
                /**
                 * @var int $index
                 * @var ActiveRecord $model
                 */
                foreach ($models as $index => $model) {
                    $key = $model->getPrimaryKey();
                    $rows[] = $this->generateRow($model, $key, $index);
                }
            }
        } else {
            $dp->pagination->setPageSize($this->batchSize);
            $dp->pagination->page = 0;
            $models = $dp->getModels();
            while (count($models) > 0) {
                foreach ($models as $index => $model) {
                    $rows[] = $this->generateRow($model, $model->id, $index);
                }
                if ($dp->pagination) {
                    $dp->pagination->page++;
                    $dp->refresh();
                    $models = $dp->getModels();
                } else {
                    $models = [];
                }
            }
        }

        return $rows;
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
     * @return string
     */
    protected function getColumnValue($model, $key, $index, Column $column): ?string
    {
        $output = null;
        $savedValue = $column->value;

        if ($column instanceof ActionColumn || $column instanceof CheckboxColumn) {
            return null;
        }

        if (!empty($column->exportedValue)) {
            $column->value = $column->exportedValue;
        }

        if ($column instanceof DataColumn) {
            $output = $this->grid->formatter->format($column->getDataCellValue($model, $key, $index), $column->format);
        }

        if ($column instanceof Column) {
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
        foreach ($this->grid->columns as $n => $column) {
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
    protected function sanitizeRow($value): ?string
    {
        if ($value) {
            $value = str_replace('&nbsp;', '', strip_tags($value));

            return ltrim($value, '=+');
        }

        return null;
    }

    /**
     * Render file content
     *
     * @param $grid
     * @return string
     * @throws IOException
     * @throws UnsupportedTypeException
     * @throws WriterNotOpenedException
     */
    public function export($grid): string
    {
        $this->initExportOptions($grid);
        ob_start();
        $writer = WriterFactory::create($this->exportType);
        $writer = $this->applySettings($writer);
        $writer->openToBrowser('php://output');

        //header
        $headerRow = $this->generateHeader();
        if (!empty($headerRow)) {
            $writer->addRow($headerRow);
        }

        //body
        $bodyRows = $this->generateBody();
        foreach ($bodyRows as $row) {
            $writer->addRow($row);
        }

        //footer
        $footerRow = $this->generateFooter();
        if (!empty($footerRow)) {
            $writer->addRow($footerRow);
        }

        $writer->close();

        return ob_get_clean();
    }

    protected function applySettings($writer)
    {
        return $writer;
    }
}
