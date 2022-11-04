<?php

declare(strict_types=1);

namespace hiqdev\yii2\export\exporters;

use hiqdev\yii2\export\models\SaveManager;

interface ExporterInterface
{
    public function export(SaveManager $saveManager): void;

    public function getMimeType(): string;
}

