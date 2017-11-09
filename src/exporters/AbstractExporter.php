<?php

namespace hiqdev\yii2\export\exporters;

use hipanel\modules\domain\grid\DomainGridView;
use yii\grid\DataColumn;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQueryInterface;
use yii\grid\ActionColumn;
use yii\grid\CheckboxColumn;
use yii\grid\Column;

abstract class AbstractExporter extends \hipanel\grid\GridView
{
    use GridViewTrait;

    /**
     * @var ActiveDataProvider dataProvider
     */
    public $dataProvider;

    /**
     * @var array of columns
     */
    public $columns;

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
     * @inheritdoc
     */
    public function init()
    {
        if (empty($this->filename)) {
            $this->filename = 'report_' . time();
        }
    }

    public function initExportOptions($dataProvider, $columns)
    {
        $columns = array_diff($columns, ['checkbox', 'actions']);
        $givenGridView = new DomainGridView(['dataProvider' => $dataProvider, 'columns' => $columns]);
        $dataProvider->pagination->setPageSize(999999);
        $columns = array_intersect_key($givenGridView->columns(), array_flip($columns));

        $this->dataProvider = $dataProvider;
        $this->columns = $columns;
        $this->initColumns();
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
        if (empty($this->columns)) {
            return;
        }

        $rows = [];
        foreach ($this->columns as $column) {
            /** @var Column $column */
            $head = ($column instanceof DataColumn) ? $this->getColumnHeader($column) : $column->header;
            $rows[] = strip_tags($head);
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
        if (empty($this->columns)) {
            return;
        }

        $rows = [];
        if ($this->dataProvider instanceof ActiveQueryInterface) {
            $query = $this->dataProvider->query;
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
            $models = $this->dataProvider->getModels();
            while (count($models) > 0) {
                /**
                 * @var int $index
                 * @var \yii\db\ActiveRecord $model
                 */
                $keys = $this->dataProvider->getKeys();
                foreach ($models as $index => $model) {
                    $key = $keys[$index];
                    $rows[] = $this->generateRow($model, $key, $index);
                }

                if ($this->dataProvider->pagination) {
                    $this->dataProvider->pagination->page++;
                    $this->dataProvider->refresh();
                    $models = $this->dataProvider->getModels();
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
        foreach ($this->columns as $column) {
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
            return $column->getDataCellValue($model, $key, $index);
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

        if (empty($this->columns)) {
            return;
        }

        $rows = [];
        foreach ($this->columns as $n => $column) {
            /** @var Column $column */
            $rows[] = trim($column->footer) !== '' ? $column->footer : '';
        }

        return $rows;
    }

    /**
     * Prevention execution code on an administrator’s machine in their user’s security context.
     *
     * @param string $value
     * @return string
     */
    protected function sanitizeRow(string $value)
    {
        $value = strip_tags($value);

        return ltrim($value, '=+-@');
    }


}
