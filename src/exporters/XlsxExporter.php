<?php

namespace hiqdev\yii2\export\exporters;

use Box\Spout\Writer\WriterFactory;

class XlsxExporter extends AbstractExporter implements ExporterInterface
{
    protected string $exportType = Type::XLSX;
}
