<?php

namespace hiqdev\yii2\export\exporters;

class CsvExporter extends AbstractExporter implements ExporterInterface
{
    protected string $exportType = Type::CSV;

    public function applySettings($writer)
    {
        return $writer
            ->setFieldDelimiter($this->settings['fieldDelimiter'])
            ->setFieldEnclosure($this->settings['fieldEnclosure']);
    }
}
