<?php

declare(strict_types=1);

namespace hiqdev\yii2\export\actions;

use hipanel\actions\IndexAction;
use hiqdev\yii2\export\models\ExportJob;
use hiqdev\yii2\export\models\SaveManager;
use Yii;

class CancelExportAction extends IndexAction
{
    public function run()
    {
        $id = $this->controller->request->post('id');
        /** @var ExportJob $job */
        $job = Yii::$app->exporter->getJob($id);
        if ($job) {
            $job->deleteJob();
            $saver = new SaveManager($id);
            $saver->delete();
        }
        Yii::$app->end();
    }
}
