<?php

namespace hiqdev\yii2\export\actions;

use hipanel\actions\IndexAction;
use hiqdev\yii2\export\ExporterFactory;
use Yii;

class ExportAction extends IndexAction
{
    public function run()
    {
        return Yii::$app->response->sendContentAsFile($this->getExporter()->render(), $this->getFileName());
    }

    protected function getExporter()
    {
        return ExporterFactory::createExporter($this->getFormat(), $this->getOptions());
    }

    protected function getFormat()
    {
        return Yii::$app->request->get('format');
    }

    protected function getOptions()
    {
        return [
            'dataProvider' => $this->getDataProvider(),
        ];
    }

    protected function getFileName()
    {
        return mt_rand();
    }
}
