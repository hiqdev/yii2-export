<?php

declare(strict_types=1);

namespace hiqdev\yii2\export\commands;

use Yii;
use yii\console\Controller;
use yii\helpers\Console;

class ExporterController extends Controller
{
    public function actionStart()
    {
        file_put_contents(Yii::getAlias('@runtime/exporter.txt'), time());
        $this->stdout('Done!', Console::FG_GREEN, Console::BOLD);
        $this->stdout(PHP_EOL);
    }
}
