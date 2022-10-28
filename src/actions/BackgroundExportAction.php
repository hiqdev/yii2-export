<?php

declare(strict_types=1);

namespace hiqdev\yii2\export\actions;

use hipanel\actions\IndexAction;
use Yii;

class BackgroundExportAction extends IndexAction
{
    public function run(): void
    {
        if ($this->controller->request->isAjax) {
            ignore_user_abort(true);
            set_time_limit(0);

            ob_start();

            header('Connection: close');
            header('Content-Length: ' . ob_get_length());
            ob_end_flush();
            @ob_flush();
            flush();
            fastcgi_finish_request(); // required for PHP-FPM (PHP > 5.3.3)

            $id = $this->controller->request->post('id');
            $representation = $this->ensureRepresentationCollection()->getByName($this->getUiModel()->representation);
            Yii::$app->exporter->runJob($id, $this, $representation->getColumns());


            die(); // a must especially if set_time_limit=0 is used and the task ends
        }
        Yii::$app->end();
    }
}
