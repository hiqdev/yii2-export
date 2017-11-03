<?php

namespace hiqdev\yii2\export\actions;

use hiqdev\yii2\export\exporters\ExporterFactoryInterface;
use hiqdev\yii2\export\exporters\Type;
use hiqdev\yii2\export\models\CsvSettings;
use hipanel\actions\IndexAction;
use Yii;

class ExportAction extends IndexAction
{
    /**
     * @var ExporterFactoryInterface
     */
    private $exporterFactory;

    public function __construct($id, $controller, ExporterFactoryInterface $exporterFactory)
    {
        parent::__construct($id, $controller);
        $this->exporterFactory = $exporterFactory;
    }

    public function run()
    {
        $type = $this->getType();
        $exporter = $this->exporterFactory->build($type);
//        $settings = $this->loadSettings($type);
//        if ($settings !== null) {
//            $settings->applyTo($exporter);
//        }

        return Yii::$app->response->sendContentAsFile($exporter->export($this->getDataProvider()), $exporter->getFileName());
    }

    protected function getType()
    {
        return Yii::$app->request->get('format');
    }

    public function loadSettings($type)
    {
        // todo
        $map = [Type::CSV => CsvSettings::class];

        $settings = new $map[$type];
        $settings->load([], '');
        $settings->validate();

        return $settings;
    }
}
