<?php

namespace hiqdev\yii2\export\widgets;

use hiqdev\yii2\export\exporters\Type;
use Yii;
use yii\base\Widget;
use yii\bootstrap\ButtonDropdown;

class IndexPageExportLinks extends Widget
{
    public function run()
    {
        return ButtonDropdown::widget([
            'label' => '<i class="fa fa-share-square-o"></i>&nbsp;' . Yii::t('hiqdev.export', 'Export'),
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
                'label' => '<i class="fa fa-file-code-o"></i>' . Yii::t('hiqdev.export', 'CSV'),
                'encode' => false,
                'linkOptions' => [
                    'data' => [
                        'pjax' => 0,
                    ],
                ],
            ],
            [
                'url' => array_merge(['export', 'format' => Type::TSV], $currentParams),
                'label' => '<i class="fa fa-file-code-o"></i>' . Yii::t('hiqdev.export', 'TSV'),
                'encode' => false,
                'linkOptions' => [
                    'data' => [
                        'pjax' => 0,
                    ],
                ],
            ],
            [
                'url' => array_merge(['export', 'format' => Type::XLSX], $currentParams),
                'label' => '<i class="fa fa-file-excel-o"></i>' . Yii::t('hiqdev.export', 'Excel XLSX'),
                'encode' => false,
                'linkOptions' => [
                    'data' => [
                        'pjax' => 0,
                    ],
                ],
            ],
        ];
    }

    protected function getCurrentParams()
    {
        return Yii::$app->getRequest()->getQueryParams();
    }
}
