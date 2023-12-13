<?php

declare(strict_types=1);

namespace hiqdev\yii2\export\assets;

use yii\web\AssetBundle;

class ClipboardJsCdnAsset extends AssetBundle
{
    public $sourcePath = __DIR__;
    public $js = ['https://cdn.jsdelivr.net/npm/clipboard@2.0.11/dist/clipboard.min.js'];
}
