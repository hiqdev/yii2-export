<?php

declare(strict_types=1);

namespace hiqdev\yii2\export\actions;

use hipanel\actions\IndexAction;
use hiqdev\yii2\export\models\BackgroundExport;
use Yii;

class DownloadExportAction extends IndexAction
{
    public function run()
    {
        $id = $this->controller->request->get('id');
        /** @var BackgroundExport $job */
        $job = Yii::$app->exporter->getJob($id);
        if ($job && $job->getStatus() === BackgroundExport::STATUS_SUCCESS) {
            $job->deleteJob();
            $report = Yii::$app->cache->get([$id, 'report']);
            $stream = fopen('data://' . $report['getMimeType'] . ';base64,' . base64_encode($report['data']), 'rb');

            return $this->controller->response->sendStreamAsFile($stream, $report['filename']);
        }
        Yii::$app->end();
    }
}
