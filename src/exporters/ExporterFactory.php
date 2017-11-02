<?php

namespace hiqdev\yii2\export\exporters;

class ExporterFactory implements Type
{
    public static function create($format, $options)
    {
        switch ($format) {
            case Type::CSV:
                return new CSVExporter($options);
            case Type::TSV:
                return new TSVExporter($options);
            case Type::XLSX:
                return new XLSXExporter($options);
        }

        throw new \InvalidArgumentException('Unknown export format given');
    }
}
