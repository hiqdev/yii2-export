<?php

declare(strict_types=1);

namespace hiqdev\yii2\export\widgets;

use yii\base\Widget;

class ExportProgress extends Widget
{
    public function run()
    {
        return $this->render('ExportProgress');
    }
}
