<?php

declare(strict_types=1);

namespace hiqdev\yii2\export\actions;

use hipanel\actions\IndexAction;
use hipanel\actions\RunProcessAction;
use Yii;

class StartExportAction extends IndexAction
{
    public function run()
    {
        $jobId = time();
        if ($this->controller->request->isAjax) {
            $action = new RunProcessAction('start-export', $this->controller);
            $action->onGettingProcessId = fn() => $jobId;
            $action->onRunProcess = function () use ($jobId) {
                $representation = $this->ensureRepresentationCollection()->getByName($this->getUiModel()->representation);

                Yii::$app->exporter->runJob((string)$jobId, $this, $representation->getColumns()); // todo: fire event!
            };

            $action->run();
        }

//        return $this->controller->asJson(['jobId' => $jobId]);
    }
}
