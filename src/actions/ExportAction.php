<?php

declare(strict_types=1);

namespace hiqdev\yii2\export\actions;

use hipanel\actions\IndexAction;
use hiqdev\yii2\export\components\Exporter;
use hiqdev\yii2\export\models\SaveManager;
use yii\web\Response;
use Yii;

class ExportAction extends IndexAction
{
    public function run(): Response
    {
        $representation = $this->ensureRepresentationCollection()->getByName($this->getUiModel()->representation);
        /** @var Exporter $exporter */
        $exporter = Yii::$app->exporter->prepare($this, $representation->getColumns());
        $id = md5($this->controller->request->getAbsoluteUrl());
        $filename = $id . '.' . $exporter->exportType;
        $saver = new SaveManager($id);
        $exporter->export($saver);
        $stream = $saver->getStream($exporter->getMimeType());
        $saver->delete();

        return $this->controller->response->sendStreamAsFile($stream, $filename);
    }
}
