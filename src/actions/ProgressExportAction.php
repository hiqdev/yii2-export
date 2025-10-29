<?php

declare(strict_types=1);


namespace hiqdev\yii2\export\actions;

use hipanel\actions\ProgressAction;
use hiqdev\yii2\export\models\ExportJob;
use hiqdev\yii2\export\models\ExportStatus;

class ProgressExportAction extends ProgressAction
{
    public function init(): void
    {
        $this->onProgress = function () {
            $id = $this->controller->request->get('id', '');
            $job = ExportJob::findOrCreate($id);
            if ($job->isNew()) {
                return json_encode(
                    ['id' => $id, 'status' => ExportStatus::RUNNING->value, 'progress' => 0],
                    JSON_THROW_ON_ERROR
                );
            }
            $this->needToTerminate = $job->needToTerminate();
            $data = $job->toArray();

            return json_encode($data, JSON_THROW_ON_ERROR);
        };
    }
}
