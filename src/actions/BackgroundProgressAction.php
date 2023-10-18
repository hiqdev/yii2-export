<?php

declare(strict_types=1);

namespace hiqdev\yii2\export\actions;

use hipanel\actions\ProgressAction;
use hiqdev\yii2\export\models\BackgroundExport;
use Yii;

class BackgroundProgressAction extends ProgressAction
{
    public function init(): void
    {
        $this->onProgress = function () {
            $id = $this->controller->request->get('id');
            /* @var $job BackgroundExport */
            $job = Yii::$app->exporter->getJob($id);
            $status = $job->getStatus();

            return json_encode(
                ['id' => $id, 'status' => $status, 'progress' => $job->getProgress()],
                JSON_THROW_ON_ERROR
            );
        };
    }
}
