<?php

namespace hiqdev\yii2\export\exporters;

use Yii;
use yii\base\InvalidConfigException;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQueryInterface;
use yii\grid\DataColumn;
use yii\helpers\Inflector;

trait GridViewTrait
{
    /**
     * @see \yii\grid\GridView::initColumns()
     *
     * @param $columns array of columns
     * @return array of columns
     */
    protected function initColumns ()
    {
        if (empty($this->grid->columns)) {
            $this->guessColumns();
        }
        foreach ($this->grid->columns as $i => $column) {
            if (is_string($column)) {
                $column = $this->createDataColumn($column);
            } else if ($column instanceof DataColumn) {
                continue;
            } else {
                $column = Yii::createObject(array_merge([
                    'class' => $this->grid->dataColumnClass ? : DataColumn::class,
                    'grid' => $this->grid,
                ], $column));
            }
            if (!$column->visible) {
                unset($this->grid->columns[$i]);
                continue;
            }
            $this->grid->columns[$i] = $column;
        }
    }

    /**
     * @see \yii\grid\GridView::createDataColumn()
     * @inheritdoc
     */
    protected function createDataColumn($text)
    {
        if (!preg_match('/^([^:]+)(:(\w*))?(:(.*))?$/', $text, $matches)) {
            throw new InvalidConfigException('The column must be specified in the format of "attribute", "attribute:format" or "attribute:format:label"');
        }

        $a =  Yii::createObject([
            'class' => $this->grid->dataColumnClass ? : DataColumn::class,
            'grid' => $this->grid,
            'attribute' => $matches[1],
            'format' => isset($matches[3]) ? $matches[3] : 'text',
            'label' => isset($matches[5]) ? $matches[5] : null,
        ]);

        return $a;
    }

    /**
     * @see \yii\grid\GridView::getColumnHeader($col)
     * @inheritdoc
     */
    public function getColumnHeader($col, $attribute = null)
    {
        if ($attribute) {
            $col->attribute = $attribute;
        }
        if ($col->header !== null || ($col->label === null && $col->attribute === null)) {
            return trim($col->header) !== '' ? $col->header : $col->grid->emptyCell;
        }
        $provider = $this->grid->dataProvider;
        if ($col->label === null) {
            if ($provider instanceof ActiveDataProvider && $provider->query instanceof ActiveQueryInterface) {
                /**
                 * @var \yii\db\ActiveRecord $model
                 */
                $model = new $provider->query->modelClass;
                $label = $model->getAttributeLabel($col->attribute);
            } else {
                $models = $provider->getModels();
                if (($model = reset($models)) instanceof Model) {
                    $label = $model->getAttributeLabel($col->attribute);
                } else {
                    $label = Inflector::camel2words($col->attribute);
                }
            }
        } else {
            $label = $col->label;
        }
        return $label;
    }

    /**
     * @todo improve the getModels()
     * @see \yii\grid\GridView::guessColumns()
     * @inheritdoc
     */
    protected function guessColumns()
    {
        $models = $this->grid->dataProvider->getModels();
        $model = reset($models);
        if (is_array($model) || is_object($model)) {
            foreach ($model as $name => $value) {
                $this->grid->columns[] = $name;
            }
        }
    }
}