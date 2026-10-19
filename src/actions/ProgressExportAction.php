<?php

declare(strict_types=1);


namespace hiqdev\yii2\export\actions;

use hipanel\actions\ProgressAction;
use hiqdev\yii2\export\models\ExportJob;
use hiqdev\yii2\export\models\ExportStatus;

class ProgressExportAction extends ProgressAction
{
    /**
     * Longer than the 120s HIAPI request timeout (AbstractExporter::EXPORT_REQUEST_TIMEOUT), during which
     * the job state legitimately does not change.
     */
    private const int STALE_AFTER_SECONDS = 150;

    public function init(): void
    {
        // Keep the stream open until the job reaches a terminal state, including the stale one below;
        // closing it silently after 60s idle left the user without any answer.
        $this->maxIdleSeconds = self::STALE_AFTER_SECONDS + 40;
        $this->onProgress = function () {
            $id = $this->controller->request->get('id', '');
            $job = ExportJob::findOrCreate($id);
            if ($job->isNew()) {
                return json_encode(
                    ['id' => $id, 'status' => ExportStatus::RUNNING->value, 'progress' => 0],
                    JSON_THROW_ON_ERROR
                );
            }
            if ($job->isStale(self::STALE_AFTER_SECONDS)) {
                $job->cancel('The export process stopped responding and was terminated. Please try again or narrow the filters.');
            }
            $this->needToTerminate = $job->needToTerminate();
            $data = $job->toArray();

            return json_encode($data, JSON_THROW_ON_ERROR);
        };
    }
}
