<?php

declare(strict_types=1);

namespace hiqdev\yii2\export\assets;

use yii\web\AssetBundle;

class ExporterAssets extends AssetBundle
{
    public $sourcePath = __DIR__ . '/js';
    public $js = ['exporter.js'];
}
