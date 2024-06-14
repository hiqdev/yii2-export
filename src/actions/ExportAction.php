<?php

declare(strict_types=1);

namespace hiqdev\yii2\export\actions;

use hipanel\actions\IndexAction;
use hiqdev\yii2\export\exporters\ExporterInterface;
use hiqdev\yii2\export\helpers\SaveManager;
use yii\web\Response;
use Yii;

class ExportAction extends IndexAction
{
    public function run(): Response
    {
        $representation = $this->ensureRepresentationCollection()->getByName($this->getUiModel()->representation);
        /** @var ExporterInterface $exporter */
        $exporter = Yii::$app->exporter->prepare($this, $representation->getColumns());
        $id = md5($this->controller->request->getAbsoluteUrl());
        $filename = implode('.', ['report_' . time(), $exporter->exportType->value]);
        $saver = new SaveManager($id);
        $exporter->export($saver);
        $stream = $saver->getStream($exporter->getMimeType());
        $saver->delete();

        return $this->controller->response->sendStreamAsFile($stream, $filename);
    }
}
