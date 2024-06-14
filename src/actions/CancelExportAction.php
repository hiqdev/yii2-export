<?php declare(strict_types=1);

namespace hiqdev\yii2\export\actions;

use hipanel\actions\IndexAction;
use hiqdev\yii2\export\models\ExportJob;
use Yii;

class CancelExportAction extends IndexAction
{
    public function run(): void
    {
        $id = $this->controller->request->post('id', '');
        $job = ExportJob::findOrNew($id);
        $job->delete();
        Yii::$app->end();
    }
}
