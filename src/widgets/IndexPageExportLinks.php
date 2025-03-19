<?php

declare(strict_types=1);

namespace hiqdev\yii2\export\widgets;

use Closure;
use hipanel\helpers\Url;
use hiqdev\yii2\export\assets\ExporterAssets;
use hiqdev\yii2\export\exporters\ExportType;
use Yii;
use yii\base\Widget;
use yii\bootstrap\ButtonDropdown;
use yii\helpers\Html;

class IndexPageExportLinks extends Widget
{
    public array|Closure $exportVariants = [];

    public function init(): void
    {
        if (is_array($this->exportVariants) && empty($this->exportVariants)) {
            $this->exportVariants = $this->getExportVariants();
        } else if (is_callable($this->exportVariants)) {
            $this->exportVariants = call_user_func($this->exportVariants, $this->getExportVariants());
        }
    }

    public function run()
    {
        ExporterAssets::register($this->view);
        $this->view->registerJs(/** @lang JavaScript */ '
            ;(($) => {
              $("a.export-report-link[data-export-url]").exporter();
            })(jQuery);
        ');

        return ButtonDropdown::widget([
            'label' => '<i class="fa fa-share-square-o"></i>&nbsp;' . Yii::t('hiqdev.export', 'Export'),
            'encodeLabel' => false,
            'options' => ['id' => 'export-btn', 'class' => 'btn-default btn-sm'],
            'dropdown' => [
                'items' => $this->getItems(),
            ],
        ]);
    }

    private function getItems(): array
    {
        $items = [];
        foreach ($this->exportVariants as $type => $link) {
            if (is_array($link)) {
                $items[] = $link;
                continue;
            }
            $icon = match (ExportType::tryFrom($type) ?? $type) {
                ExportType::XLSX => Html::tag('i', null, ['class' => 'fa fa-fw fa-file-excel-o']),
                ExportType::MD => Html::tag('i', null, ['class' => 'fa fa-fw fa-download']),
                default => Html::tag('i', null, ['class' => 'fa fa-fw fa-file-code-o']),
            };
            $url = $this->combineUrl('start-export', $type);
            $items[] = [
                'url' => $url,
                'label' => $icon . $link,
                'encode' => false,
                'linkOptions' => [
                    'class' => 'export-report-link',
                    'data' => [
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

    private function getExportVariants(): array
    {
        return [
            ExportType::CSV->value => 'CSV',
            ExportType::TSV->value => 'TSV',
            ExportType::XLSX->value => 'Excel XLSX',
            ExportType::MD->value => 'Clipboard MD',
        ];
    }
}
