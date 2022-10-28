<?php

namespace hiqdev\yii2\export\exporters;

class TsvExporter extends CsvExporter
{
    public function getMimeType(): string
    {
        return 'text/tsv';
    }
}
