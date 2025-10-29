<?php

declare(strict_types=1);


namespace hiqdev\yii2\export\exporters;

use OpenSpout\Writer\WriterInterface;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Writer\XLSX\Properties;
use OpenSpout\Writer\XLSX\Writer;
use Yii;

class XLSXExporter extends AbstractExporter
{
    public function getMimeType(): string
    {
        return 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
    }

    public function getExportType(): ExportType
    {
        return ExportType::XLSX;
    }

    protected function getWriter(): ?WriterInterface
    {
        $options = $this->getOptions();

        return new Writer($options);
    }

    private function getOptions(): Options
    {
        $options = new Options();
        $options->setProperties($this->getProperties());

        return $options;
    }

    private function getProperties(): Properties
    {
        $orgName = Yii::$app->params['organization.name'] ?? '';

        return new Properties(
            title: 'HiPanel Report',
            application: 'Yii2 Export',
            creator: $orgName,
            lastModifiedBy: $orgName,
        );
    }
}
