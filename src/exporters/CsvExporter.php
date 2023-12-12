<?php

namespace hiqdev\yii2\export\exporters;

use Box\Spout\Writer\WriterInterface;

class CsvExporter extends AbstractExporter
{
    public Type $exportType = Type::CSV;

    protected function applySettings(WriterInterface $writer): WriterInterface
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
