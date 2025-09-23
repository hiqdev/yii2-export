<?php

declare(strict_types=1);


namespace hiqdev\yii2\export\actions;

use hipanel\actions\IndexAction;
use hipanel\actions\RunProcessAction;
use Yii;
use yii\web\BadRequestHttpException;

class StartExportAction extends IndexAction
{
    public function run(): void
    {
        if ($this->controller->request->isAjax) {
            $action = new RunProcessAction('start-export', $this->controller);
            $action->onRunProcess = function () {
                $id = $this->controller->request->post('export_id');
                if (!ctype_digit($id) || strlen($id) !== 10) {
                    throw new BadRequestHttpException('Invalid export ID format');
                }
                $representation = $this->ensureRepresentationCollection()->getByName($this->getUiModel()->representation);
                Yii::$app->exporter->runJob($id, $this, $representation->getColumns());
            };
            $action->run();
        }
        Yii::$app->end();
    }
}
