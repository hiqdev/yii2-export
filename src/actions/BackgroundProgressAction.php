<?php

declare(strict_types=1);

namespace hiqdev\yii2\export\actions;

use Exception;
use hipanel\actions\IndexAction;
use hiqdev\yii2\export\models\BackgroundExport;
use Yii;

class BackgroundProgressAction extends IndexAction
{
    public function run()
    {
        $id = $this->controller->request->get('id');
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');
        while (true) {
            try {
                /* @var $job BackgroundExport */
                $job = Yii::$app->exporter->getJob($id);
                $status = $job->getStatus();
            } catch (Exception $exception) {
                break;
            }
            $json = json_encode(
                ['id' => $id, 'status' => $status, 'progress' => $job->getProgress()],
                JSON_THROW_ON_ERROR
            );
            echo "id: " . $id . PHP_EOL;
            echo "data: " . $json . PHP_EOL;
            echo PHP_EOL;
            ob_flush();
            flush();
            sleep(1);
            if ($status === BackgroundExport::STATUS_SUCCESS) {
                break;
            }
        }
        die();
    }
}
