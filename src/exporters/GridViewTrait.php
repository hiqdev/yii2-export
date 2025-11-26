<?php

namespace hiqdev\yii2\export\exporters;

use Yii;
use yii\base\InvalidConfigException;
use yii\grid\DataColumn;

trait GridViewTrait
{
    /**
     * @see \yii\grid\GridView::createDataColumn()
     * @inheritdoc
     */
    protected function createDataColumn($text)
    {
        if (!preg_match('/^([^:]+)(:(\w*))?(:(.*))?$/', $text, $matches)) {
            throw new InvalidConfigException(
                'The column must be specified in the format of "attribute", "attribute:format" or "attribute:format:label"'
            );
        }

        return Yii::createObject([
            'class' => $this->grid->dataColumnClass ?: DataColumn::class,
            'grid' => $this->grid,
            'attribute' => $matches[1],
            'format' => isset($matches[3]) ? $matches[3] : 'text',
            'label' => isset($matches[5]) ? $matches[5] : null,
        ]);
    }

    /**
     * @see \yii\grid\GridView::getColumnHeader($col)
     * @inheritdoc
     */
    public function getColumnHeader($column)
    {
        return $this->sanitizeRow($column->renderHeaderCell());
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
