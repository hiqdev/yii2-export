<?php

declare(strict_types=1);


namespace hiqdev\yii2\export\exporters;

use OpenSpout\Writer\CSV\Options;
use OpenSpout\Writer\CSV\Writer;
use OpenSpout\Writer\WriterInterface;

class TSVExporter extends CSVExporter
{
    public function getMimeType(): string
    {
        return 'text/tsv';
    }

    public function getExportType(): ExportType
    {
        return ExportType::TSV;
    }

    protected function getWriter(): ?WriterInterface
    {
        $options = new Options();
        $options->FIELD_DELIMITER = "\t";

        return new Writer($options);
    }
}
