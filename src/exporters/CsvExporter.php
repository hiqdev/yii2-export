<?php

namespace hiqdev\yii2\export\exporters;

class CsvExporter extends AbstractExporter
{
    public string $exportType = Type::CSV;

    protected function applySettings($writer)
    {
        return $writer
            ->setFieldDelimiter($this->settings['fieldDelimiter'])
            ->setFieldEnclosure($this->settings['fieldEnclosure']);
    }

    public function getMimeType(): string
    {
        return 'text/csv';
    }
}
