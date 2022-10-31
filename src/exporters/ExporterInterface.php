<?php

declare(strict_types=1);

namespace hiqdev\yii2\export\exporters;

interface ExporterInterface
{
    public function export(): string;

    public function getMimeType(): string;
}

