<?php

namespace hiqdev\yii2\export\actions;

use hiqdev\yii2\export\exporters\ExporterFactoryInterface;
use hiqdev\yii2\export\exporters\Type;
use hiqdev\yii2\export\models\CsvSettings;
use hipanel\actions\IndexAction;
use hiqdev\yii2\export\models\TsvSettings;
use hiqdev\yii2\export\models\XlsxSettings;
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
        $settings = $this->loadSettings($type);
        if ($settings !== null) {
            $settings->applyTo($exporter);
        }
        $columns = $this->ensureRepresentationCollection()->getByName($this->controller->indexPageUiOptionsModel->representation)->getColumns();

        $result = $exporter->export($this->getDataProvider(), $columns);
        $filename = $exporter->filename . '.' . $type;

        return Yii::$app->response->sendContentAsFile($result, $filename);
    }

    protected function getType()
    {
        return Yii::$app->request->get('format');
    }

    public function loadSettings($type)
    {
        $map = [
            Type::CSV => CsvSettings::class,
            Type::TSV => TsvSettings::class,
            Type::XLSX => XlsxSettings::class,
        ];

        $settings = Yii::createObject($map[$type]);
        if ($settings->load(Yii::$app->request->get(), '') && $settings->validate()) {
            return $settings;
        }

        return null;
    }
}
