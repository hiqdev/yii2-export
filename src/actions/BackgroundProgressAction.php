<?php

declare(strict_types=1);

namespace hiqdev\yii2\export\actions;

use hipanel\actions\ProgressAction;
use hiqdev\yii2\export\components\Exporter;
use hiqdev\yii2\export\models\BackgroundExport;
use Yii;

class BackgroundProgressAction extends ProgressAction
{
    public function init(): void
    {
        $this->onProgress = function () {
            $id = $this->controller->request->get('id');
            /** @var Exporter $exporter */
            $exporter = Yii::$app->exporter;
            if (!$exporter->isExistsJob($id)) {
                return json_encode(
                    ['id' => $id, 'status' => BackgroundExport::STATUS_RUNNING, 'progress' => 0],
                    JSON_THROW_ON_ERROR
                );
            }
            $job = $exporter->getJob($id);
            $status = $job->getStatus();
            $this->needsToBeEnd = $status === BackgroundExport::STATUS_SUCCESS;

            return json_encode(
                ['id' => $id, 'status' => $status, 'progress' => $job->getProgress()],
                JSON_THROW_ON_ERROR
            );
        };
    }
}
