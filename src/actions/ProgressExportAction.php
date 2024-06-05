<?php

declare(strict_types=1);

namespace hiqdev\yii2\export\actions;

use hipanel\actions\ProgressAction;
use hiqdev\yii2\export\components\Exporter;
use hiqdev\yii2\export\models\ExportJob;
use Yii;

class ProgressExportAction extends ProgressAction
{
    public function init(): void
    {
        $this->onProgress = function () {
            $id = $this->controller->request->get('id');
            /** @var Exporter $exporter */
            $exporter = Yii::$app->exporter;
            if (!$exporter->isExistsJob($id)) {
                return json_encode(
                    ['id' => $id, 'status' => ExportJob::STATUS_RUNNING, 'progress' => 0],
                    JSON_THROW_ON_ERROR
                );
            }
            $job = $exporter->getJob($id);
            $status = $job->getStatus();
            $this->needsToBeEnd = in_array(
                $status,
                [ExportJob::STATUS_SUCCESS, ExportJob::STATUS_ERROR, ExportJob::STATUS_CANCEL],
                true
            );
            $data = $job->toArray();

            return json_encode($job->toArray(), JSON_THROW_ON_ERROR);
        };
    }
}
