<?php

declare(strict_types=1);

namespace hiqdev\yii2\export\widgets;

use hipanel\helpers\Url;
use hiqdev\yii2\export\assets\ExporterAssets;
use hiqdev\yii2\export\exporters\Type;
use Yii;
use yii\base\Widget;
use yii\bootstrap\ButtonDropdown;
use yii\helpers\Html;

class IndexPageExportLinks extends Widget
{
    public function run()
    {
        $progressUrl = 'progress-export';
        $downloadUrl = 'download-export';
        $step0Msg = Yii::t('hiqdev.export', 'Downloading');
        $step1Msg = Yii::t('hiqdev.export', 'Initialization');
        $step4Msg = Yii::t('hiqdev.export', 'Wait until the report is downloaded');
        ExporterAssets::register($this->view);
        $this->view->registerJs(/** @lang JavaScript */ "
            (($) => {
              const bar = $('#export-progress-box');
              const progress = bar.find('.progress-bar').eq(0);
              const progressText = bar.find('.progress-text').eq(0);
              const progressNumber = bar.find('.progress-number').eq(0);
              const progressDescription = bar.find('.progress-description').eq(0);
              const progressCanceExport = bar.find('button').eq(0);
              const exportBtn = $('#export-btn');
              const resetExport = () => {
                bar.hide(500, () => {
                  progressText.text('');
                  progressNumber.text('');
                  progressDescription.text('');
                  exportBtn.attr('disabled', false).removeClass('disabled');
                  progress.css('width', 0);
                  progressCanceExport.show();
                });
              };
              const downloadWithProggress = (id, ext) => {
                progressText.text('$step0Msg');
                const xhr = $.ajaxSettings.xhr();
                xhr.onreadystatechange = function () {
                  if (this.readyState === 4 && this.status === 200) {
                    const filename = 'report_' + id + '.' + ext;
                    if (ext === 'md') {
                      function copyText(text) {
                        const listener = function (ev) {
                          ev.preventDefault();
                          ev.clipboardData.setData('text/plain', text);
                        };
                        document.addEventListener('copy', listener);
                        document.execCommand('copy');
                        document.removeEventListener('copy', listener);
                      }
                      if (navigator.clipboard) {
                        window.navigator.permissions.query({ name: 'clipboard-write' }).then((result) => {
                          if (result.state == 'granted' || result.state == 'prompt') {
                            navigator.clipboard.writeText(xhr.responseText).then(
                              () => {
                                hipanel.notify.success('Clipped!');
                              },
                              (e) => {
                                hipanel.notify.error('Failed to copy to clipboard');
                                console.error(e);
                              },
                            );
                          }
                        });
                      } else {
                        copyText(xhr.responseText);
                        hipanel.notify.success('Clipped!');
                      }
                    } else {
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
                  }
                };
                xhr.onprogress = function (event) {
                  if (event.lengthComputable) {
                    const percentComplete = Math.floor((event.loaded / event.total) * 100) + '%';
                    progress.css('width', percentComplete);
                    progressNumber.text(percentComplete);
                    progressDescription.text('$step4Msg');
                    if (percentComplete === '100%') {
                      resetExport();
                    }
                  }
                };
                xhr.responseType = ext === 'md' ? 'text' : 'blob';
                xhr.open('GET', '$downloadUrl' + '?id=' + id, true);
                xhr.send();
              }
              const startExport = (event) => {
                if (!window.EventSource) {
                  return;
                }
                event.preventDefault();
                const exportUrl = event.target.dataset.exportUrl;
                const cancelUrl = 'cancel-export';
                const id = event.target.dataset['id'];
                const url = new URL(exportUrl);
                exportBtn.attr('disabled', true).addClass('disabled');
                bar.show(500, () => progressText.text('$step1Msg'));
                progress.css('width', '100%');
                hipanel.runProcess(exportUrl, { id }, null, null, function (data, status) {
                  debugger
                });
                setTimeout(() => {
                  if (!id) {
                    return;
                  }
                  hipanel.progress(`$progressUrl?id=\${id}`, (es) => {
                    const onPageUnload = function (e) {
                      e.preventDefault();
                      e.returnValue = '';
                      es.close();
                      hipanel.runProcess(cancelUrl, { id });
                    }
                    window.addEventListener('beforeunload', onPageUnload);
                    progressCanceExport.click(function () {
                      hipanel.runProcess(cancelUrl, { id });
                      es.close();
                      window.removeEventListener('beforeunload', onPageUnload);
                      resetExport();
                    });
                  }).onMessage((event, es) => {
                    const data = JSON.parse(event.data);
                    if (data.status === 'running') {
                      progressText.text(data.taskName || 'Running');
                      const percentComplete = Math.floor((data.progress / data.total) * 100) + '%';
                      progress.css('width', percentComplete);
                      progressNumber.text(data.progress > 0 ? data.progress + ' / ' + data.total + ' ' + (data.unit || '') : '...');
                    } else {
                      progress.css('width', '100%');
                      progressNumber.text('');
                      progressCanceExport.hide(() => {
                        es.close();
                      });
                      if (data.status === 'success') {
                        downloadWithProggress(id, url.searchParams.get('format'));
                      } else {
                        hipanel.notify.error(`Status: \${data.status}\nMessage: \${data.errorMessage}`);
                        resetExport();
                      }
                    }
                  });
                }, 1000);
              }
//              $(document).on('click', 'a.export-report-link', startExport);
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
        foreach ([Type::CSV->value => 'CSV', Type::TSV->value => 'TSV', Type::XLSX->value => 'Excel XLSX', Type::MD->value => 'Clipboard MD'] as $type => $label) {
            $icon = match (Type::from($type)) {
                Type::XLSX => Html::tag('i', null, ['class' => 'fa fa-fw fa-file-excel-o']),
                Type::MD => Html::tag('i', null, ['class' => 'fa fa-fw fa-download']),
                default => Html::tag('i', null, ['class' => 'fa fa-fw fa-file-code-o']),
            };
            $url = $this->combineUrl('export', $type);
            $items[] = [
                'url' => $url,
                'label' => $icon . $label,
                'encode' => false,
                'linkOptions' => [
                    'class' => 'export-report-link',
                    'data' => [
                        'id' => time(),
                        'export-url' => $this->combineUrl('start-export', $type),
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
