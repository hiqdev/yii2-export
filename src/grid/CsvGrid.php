<?php

namespace hiqdev\yii2\export\grid;

use hipanel\grid\DataColumn;
use Yii;
use yii\base\InvalidConfigException;

class CsvGrid extends \yii2tech\csvgrid\CsvGrid
{
    public $dataColumnClass = DataColumn::class;

    public $resizableColumns;

    public $id;

    /**
     * {@inheritdoc}
     */
    protected function composeHeaderRow()
    {
        $rows = [];
        foreach (parent::composeHeaderRow() as $row) {
            $rows[] = $this->sanitise($row);
        }

        return $rows;
    }

    /**
     * {@inheritdoc}
     */
    protected function composeBodyRow($model, $key, $index)
    {
        $cells = [];
        foreach ($this->columns as $column) {
            $cells[] = strip_tags($column->renderDataCell($model, $key, $index));
        }
        return $cells;
    }

    /**
     * {@inheritdoc}
     */
    protected function initColumns($model)
    {
        if (empty($this->columns)) {
            $this->guessColumns($model);
        }
        foreach ($this->columns as $i => $column) {
            if (is_string($column)) {
                $column = $this->createDataColumn($column);
            } else {
                $column = Yii::createObject(array_merge([
                    'class' => $this->dataColumnClass ?: DataColumn::className(),
                    'grid' => $this,
                ], $column));
            }
            if (!$column->visible) {
                unset($this->columns[$i]);
                continue;
            }
            $this->columns[$i] = $column;
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function createDataColumn($text)
    {
        if (!preg_match('/^([^:]+)(:(\w*))?(:(.*))?$/', $text, $matches)) {
            throw new InvalidConfigException('The column must be specified in the format of "attribute", "attribute:format" or "attribute:format:label"');
        }

        return Yii::createObject([
            'class' => $this->dataColumnClass ?: DataColumn::className(),
            'grid' => $this,
            'attribute' => $matches[1],
            'format' => isset($matches[3]) ? $matches[3] : 'text',
            'label' => isset($matches[5]) ? $matches[5] : null,
        ]);
    }

    protected function sanitise($value)
    {
        return strip_tags($value);
    }
}

