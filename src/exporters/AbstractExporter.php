<?php

namespace hiqdev\yii2\export\exporters;

use hiqdev\yii2\menus\grid\MenuColumn;
use yii\grid\DataColumn;
use yii\db\ActiveQueryInterface;
use yii\grid\ActionColumn;
use yii\grid\CheckboxColumn;
use yii\grid\Column;

abstract class AbstractExporter
{
    use GridViewTrait;

    public $grid;

    /**
     * @var bool whether to export footer or not
     */
    public $exportFooter = true;

    /**
     * @var int batch size to fetch the data provider
     */
    public $batchSize = 2000;

    /**
     * @var string filename without extension
     */
    public $filename;

    /**
     * @see ExportMenu target consts
     * @var string how the page will delivery the report
     */
    public $target;


    /**
     * Setting for exported file
     *
     * @var array
     */
    protected $settings = [];

    /**
     * Init
     *
     * @param $grid
     * @param $columns
     */
    public function initExportOptions($grid)
    {
        if (empty($this->filename)) {
            $this->filename = 'report_' . time();
        }
        foreach ($grid->columns as $i => $column) {
            if ($column instanceof CheckboxColumn || $column instanceof ActionColumn || $column instanceof MenuColumn) {
                unset($grid->columns[$i]);
            }
        }
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
    public function setSettings(array $settings)
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
                $head = $this->getColumnHeader($column, $attribute);
            } else {
                $head = $column->header;
            }
            $rows[] = $this->sanitizeRow($head);
        }

        return $rows;
    }


    /**
     * Fetch data from the data provider and create the rows array
     *
     * @return array|void
     */
    protected function generateBody()
    {
        if (empty($this->grid->columns)) {
            return;
        }

        $rows = [];
        if ($this->grid->dataProvider instanceof ActiveQueryInterface) {
            $query = $this->grid->dataProvider->query;
            foreach ($query->batch($this->batchSize) as $models) {
                /**
                 * @var int $index
                 * @var \yii\db\ActiveRecord $model
                 */
                foreach ($models as $index => $model) {
                    $key = $model->getPrimaryKey();
                    $rows[] = $this->generateRow($model, $key, $index);
                }
            }
        } else {
            $this->grid->dataProvider->pagination->setPageSize($this->batchSize);
            $models = $this->grid->dataProvider->getModels();
            while (count($models) > 0) {
                foreach ($models as $index => $model) {
                    $rows[] = $this->generateRow($model, $model->id, $index);
                }
                if ($this->grid->dataProvider->pagination) {
                    $this->grid->dataProvider->pagination->page++;
                    $this->grid->dataProvider->refresh();
                    $models = $this->grid->dataProvider->getModels();
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
    protected function generateRow($model, $key, $index)
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
     * @param $column
     * @return string
     */
    protected function getColumnValue($model, $key, $index, $column)
    {
        /** @var Column $column */
        if ($column instanceof ActionColumn || $column instanceof CheckboxColumn) {
            return '';
        } else if ($column instanceof DataColumn) {
            return $this->grid->formatter->format($column->getDataCellValue($model, $key, $index), $column->format);
        } else if ($column instanceof Column) {
            return $column->renderDataCell($model, $key, $index);
        }

        return '';
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
    protected function sanitizeRow($value)
    {
        if ($value) {
            $value = strip_tags($value);
            $value = str_replace('&nbsp;', '', $value);

            return ltrim($value, '=+-@');
        }

        return null;
    }


}
