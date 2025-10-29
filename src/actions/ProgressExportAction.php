<?php

declare(strict_types=1);


namespace hiqdev\yii2\export\actions;

use hipanel\actions\ProgressAction;
use hiqdev\yii2\export\models\ExportStatus;
use hiqdev\yii2\export\models\ExportJob;

class ProgressExportAction extends ProgressAction
{
    public function init(): void
    {
        $this->onProgress = function () {
            $id = $this->controller->request->get('id', '');
            $job = ExportJob::findOrNew($id);
            if ($job->isNew()) {
                return json_encode(
                    ['id' => $id, 'status' => ExportStatus::RUNNING->value, 'progress' => 0],
                    JSON_THROW_ON_ERROR
                );
            }
            $this->needsToBeEnd = in_array(
                $job->status,
                [ExportStatus::SUCCESS->value, ExportStatus::ERROR->value, ExportStatus::CANCEL->value],
                true
            );
            $data = $job->toArray();

            return json_encode($data, JSON_THROW_ON_ERROR);
        };
    }
}
