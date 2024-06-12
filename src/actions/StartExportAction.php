<?php declare(strict_types=1);

namespace hiqdev\yii2\export\actions;

use hipanel\actions\IndexAction;
use hipanel\actions\RunProcessAction;
use Yii;

class StartExportAction extends IndexAction
{
    public function run(): void
    {
        if ($this->controller->request->isAjax) {
            $action = new RunProcessAction('start-export', $this->controller);
            $action->onRunProcess = function () {
                $id = $this->controller->request->post('id');
                $representation = $this->ensureRepresentationCollection()->getByName($this->getUiModel()->representation);
                Yii::$app->exporter->runJob($id, $this, $representation->getColumns());
            };
            $action->run();
        }
        Yii::$app->end();
    }
}
