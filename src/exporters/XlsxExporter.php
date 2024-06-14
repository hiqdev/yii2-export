<?php

namespace hiqdev\yii2\export\exporters;

class XlsxExporter extends AbstractExporter
{
    public ExportType $exportType = ExportType::XLSX;

    public function getMimeType(): string
    {
        return 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
    }
}
