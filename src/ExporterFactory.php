<?php

namespace hiqdev\yii2\export;

use hiqdev\yii2\export\exporters\CSVExporter;
use hiqdev\yii2\export\exporters\TSVExporter;
use hiqdev\yii2\export\exporters\XLSXExporter;

class ExporterFactory implements ExporterType
{
    public static function createExporter($format, $options)
    {
        switch ($format) {
            case ExporterType::CSV:
                return new CSVExporter($options);
                break;
            case ExporterType::TSV:
                return new TSVExporter($options);
                break;
            case ExporterType::XLSX:
                return new XLSXExporter($options);
                break;
        }

        throw new \InvalidArgumentException('Unknown export format given');
    }
}
