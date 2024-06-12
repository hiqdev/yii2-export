<?php

declare(strict_types=1);

namespace hiqdev\yii2\export\exporters;

use hiqdev\yii2\export\models\ExportJob;

interface ExporterInterface
{
    public function export(ExportJob $job): void;

    public function getMimeType(): string;
}

