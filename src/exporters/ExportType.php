<?php

declare(strict_types=1);


namespace hiqdev\yii2\export\exporters;

use Yii;

enum ExportType: string
{
    case CSV = 'csv';
    case TSV = 'tsv';
    case XLSX = 'xlsx';
    case MD = 'md';

    public function label(): string
    {
        return match ($this) {
            self::CSV => 'CSV',
            self::TSV => 'TSV',
            self::XLSX => 'Excel XLSX',
            self::MD => Yii::t('hiqdev.export', 'Clipboard MD'),
        };
    }

    public static function getLabels(): array
    {
        $labels = [];
        foreach (self::cases() as $case) {
            $labels[$case->value] = $case->label();
        }

        return $labels;
    }
}
