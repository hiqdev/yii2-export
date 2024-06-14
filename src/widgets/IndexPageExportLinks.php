<?php

declare(strict_types=1);

namespace hiqdev\yii2\export\widgets;

use hipanel\helpers\Url;
use hiqdev\yii2\export\assets\ExporterAssets;
use hiqdev\yii2\export\exporters\ExportType;
use Yii;
use yii\base\Widget;
use yii\bootstrap\ButtonDropdown;
use yii\helpers\Html;

class IndexPageExportLinks extends Widget
{
    public function run()
    {
        ExporterAssets::register($this->view);
        $this->view->registerJs(/** @lang JavaScript */ "
            (($) => {
              $('a.export-report-link').exporter();
            })($);
        ");

        return ButtonDropdown::widget([
            'label' => '<i class="fa fa-share-square-o"></i>&nbsp;' . Yii::t('hiqdev.export', 'Export'),
            'encodeLabel' => false,
            'options' => ['id' => 'export-btn', 'class' => 'btn-default btn-sm'],
            'dropdown' => [
                'items' => $this->getItems(),
            ],
        ]);
    }

    protected function getItems(): array
    {
        $items = [];
        foreach ([ExportType::CSV->value => 'CSV', ExportType::TSV->value => 'TSV', ExportType::XLSX->value => 'Excel XLSX', ExportType::MD->value => 'Clipboard MD'] as $type => $label) {
            $icon = match (ExportType::from($type)) {
                ExportType::XLSX => Html::tag('i', null, ['class' => 'fa fa-fw fa-file-excel-o']),
                ExportType::MD => Html::tag('i', null, ['class' => 'fa fa-fw fa-download']),
                default => Html::tag('i', null, ['class' => 'fa fa-fw fa-file-code-o']),
            };
            $url = $this->combineUrl('start-export', $type);
            $items[] = [
                'url' => $url,
                'label' => $icon . $label,
                'encode' => false,
                'linkOptions' => [
                    'class' => 'export-report-link',
                    'data' => [
//                        'id' => md5($url),
                        'id' => time(),
                        'export-url' => $url,
                    ],
                ],
            ];
        }

        return $items;
    }

    private function combineUrl(string $variant, string $type): string
    {
        $currentParams = Yii::$app->getRequest()->getQueryParams();
        $uiRoute = Yii::$app->request->pathInfo;

        return Url::toRoute(array_merge(
            [
                $variant,
                'format' => $type,
                'route' => $uiRoute,
            ],
            $currentParams
        ),
            true);
    }
}
