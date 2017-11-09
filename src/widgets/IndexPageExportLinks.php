<?php

namespace hiqdev\yii2\export\widgets;

use hiqdev\yii2\export\exporters\Type;
use Yii;
use yii\base\Widget;
use yii\bootstrap\ButtonDropdown;
use yii\helpers\Url;

class IndexPageExportLinks extends Widget
{
    public $representationCollection;

    public function run()
    {
        return ButtonDropdown::widget([
            'label' => '<i class="fa fa-share-square-o"></i>&nbsp;' . Yii::t('hipanel', 'Export'),
            'encodeLabel' => false,
            'options' => ['class' => 'btn-default btn-sm'],
            'dropdown' => [
                'items' => $this->getItems(),
            ],
        ]);
    }

    protected function getItems()
    {
        $currentParams = $this->getCurrentParams();

        return [
            [
                'url' => array_merge(['export', 'format' => Type::CSV], $currentParams),
                'label' => '<i class="fa fa-file-code-o"></i>' . Yii::t('hipanel', 'CSV'),
                'encode' => false,
            ],
            [
                'url' => array_merge(['export', 'format' => Type::TSV], $currentParams),
                'label' => '<i class="fa fa-file-code-o"></i>' . Yii::t('hipanel', 'TSV'),
                'encode' => false,
            ],
            [
                'url' => array_merge(['export', 'format' => Type::XLSX], $currentParams),
                'label' => '<i class="fa fa-file-excel-o"></i>' . Yii::t('hipanel', 'Excel XLSX'),
                'encode' => false,
            ],
        ];
    }

    protected function getCurrentParams()
    {
        return Yii::$app->getRequest()->getQueryParams();
    }
}
