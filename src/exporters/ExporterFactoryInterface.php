<?php

declare(strict_types=1);

namespace hiqdev\yii2\export\exporters;

interface ExporterFactoryInterface
{
    public function build(ExportType $type): ExporterInterface;
}
