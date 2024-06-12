<?php

namespace hiqdev\yii2\export\exporters;

enum ExportType: string
{
    case CSV = 'csv';
    case TSV = 'tsv';
    case XLSX = 'xlsx';
    case MD = 'md';
}
