<?php

declare(strict_types=1);

namespace hiqdev\yii2\export\actions;

use hipanel\actions\IndexAction;
use yii\web\Response;
use Yii;

class ExportAction extends IndexAction
{
    public function run(): Response
    {
        $representation = $this->ensureRepresentationCollection()->getByName($this->getUiModel()->representation);
        $exporter = Yii::$app->exporter->prepare($this, $representation->getColumns());
        $filename = $exporter->filename;
        $data = $exporter->export();

        return $this->controller->response->sendContentAsFile($data, $filename);
    }
}
