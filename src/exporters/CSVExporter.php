<?php

declare(strict_types=1);


namespace hiqdev\yii2\export\exporters;

use OpenSpout\Writer\CSV\Writer;
use OpenSpout\Writer\WriterInterface;

class CSVExporter extends AbstractExporter
{
    public function getExportType(): ExportType
    {
        return ExportType::CSV;
    }

    public function getMimeType(): string
    {
        return 'text/csv';
    }

    protected function getWriter(): ?WriterInterface
    {
        return new Writer();
    }
}
