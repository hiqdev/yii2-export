<?php

namespace hiqdev\yii2\export\widgets;

use hipanel\helpers\Url;
use hiqdev\yii2\export\exporters\Type;
use Yii;
use yii\base\Widget;
use yii\bootstrap\ButtonDropdown;

class IndexPageExportLinks extends Widget
{
    public function run()
    {
        $progressUrl = 'progress-export';
        $downloadUrl = 'download-export';
        $step0Msg = Yii::t('hiqdev.export', 'Downloading');
        $step1Msg = Yii::t('hiqdev.export', 'Initialization');
        $step2Msg = Yii::t('hiqdev.export', 'Exporting...');
        $step3Msg = Yii::t('hiqdev.export', 'Wait until the report is downloaded');
        $this->view->registerJs(/** @lang JavaScript */ "
            (($) => {
              const bar = $('#export-progress-box');
              const progress = bar.find('.progress-bar').eq(0);
              const progressText = bar.find('.progress-text').eq(0);
              const progressNumber = bar.find('.progress-number').eq(0);
              const progressDescription = bar.find('.progress-description').eq(0);
              const exportBtn = $('#export-btn');
              const downloadWithProggress = (id, ext) => {
                progressText.text('$step0Msg');
                const xhr = $.ajaxSettings.xhr();
                xhr.onreadystatechange = function () {
                  if (this.readyState === 4 && this.status === 200) {
                    const filename = id + '.' + ext;
                    if (typeof window.chrome !== 'undefined') {
                      // Chrome version
                      const link = document.createElement('a');
                      link.href = window.URL.createObjectURL(xhr.response);
                      link.download = filename;
                      link.click();
                    } else if (typeof window.navigator.msSaveBlob !== 'undefined') {
                      // IE version
                      var blob = new Blob([xhr.response], { type: 'application/force-download' });
                      window.navigator.msSaveBlob(blob, filename);
                    } else if (/(Version)\/(\d+)\.(\d+)(?:\.(\d+))?.*Safari\//.test(navigator.userAgent)) {
                      const link = document.createElement('a');
                      link.href = window.URL.createObjectURL(xhr.response);
                      link.download = filename;
                      link.click();
                    } else {
                      // Firefox version
                      var file = new File([xhr.response], filename, { type: 'application/force-download' });
                      window.open(URL.createObjectURL(file));
                    }
                  }
                };
                xhr.onprogress = function (event) {
                  if (event.lengthComputable) {
                    const percentComplete = Math.floor((event.loaded / event.total) * 100) + '%';
                    progress.css('width', percentComplete);
                    progressNumber.text(percentComplete);
                    progressDescription.text('$step3Msg');
                    if (percentComplete === '100%') {
                      bar.hide(500, () => {
                        progressText.text('');
                        progressNumber.text('');
                        progressDescription.text('');
                        exportBtn.attr('disabled', false).toggleClass('disabled');
                      });
                    }
                  }
                };
                xhr.responseType = 'blob';
                xhr.open('GET', '$downloadUrl' + '?id=' + id, true);
                xhr.send();
              }
              const startExport = (event) => {
                if (!window.EventSource) {
                  return;
                }
                event.preventDefault();
                const href = event.target.dataset.backgroundExportUrl;
                const id = event.target.dataset['id'];
                const url = new URL(href);
                exportBtn.attr('disabled', true).toggleClass('disabled');
                if (id) {
                  bar.show(500, () => progressText.text('$step1Msg'));
                  $.post(href, {id: id});
                  setTimeout(() => {
                    const source = new EventSource('$progressUrl' + '?id=' + id);
                    source.addEventListener('message', event => {
                      const data = JSON.parse(event.data);
                      if (data.status === 'running') {
                        progressText.text('$step2Msg');
                        const totalElement = $('div[role=grid] .summary > b').eq(1);
                        if (totalElement.length > 0 && data.progress > 0) {
                          const total = parseInt(totalElement.text().replace(/\D/g, ''));
                          const percentComplete = Math.floor((data.progress / total) * 100) + '%';
                          progress.css('width', percentComplete);
                          progressNumber.text(data.progress + ' / ' + total);
                        }
                      } else if (data.status === 'success') {
                        progress.css('width', '100%');
                        progressNumber.text('');
                        source.close();
                        downloadWithProggress(id, url.searchParams.get('format'));
                      }
                    });
                  }, 1000);
                }
              }
              $(document).on('click', 'a.export-report-link', startExport);
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
        foreach ([Type::CSV => 'CSV', Type::TSV => 'TSV', Type::XLSX => 'Excel XLSX'] as $type => $label) {
            $icon = $type === Type::XLSX ? 'fa-file-excel-o' : 'fa-file-code-o';
            $url = $this->combineUrl('export', $type);
            $items[] = [
                'url' => $url,
                'label' => "<i class=\"fa $icon\"></i>" . $label,
                'encode' => false,
                'linkOptions' => [
                    'class' => 'export-report-link',
                    'data' => [
                        'id' => md5($url),
                        'background-export-url' => $this->combineUrl('background-export', $type),
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
