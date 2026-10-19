<?php

declare(strict_types=1);


namespace hiqdev\yii2\export\actions;

use hipanel\actions\IndexAction;
use hipanel\actions\RunProcessAction;
use hiqdev\yii2\export\components\ExportRequestFactory;
use hiqdev\yii2\export\models\ExportJob;
use Throwable;
use Yii;
use yii\web\BadRequestHttpException;

class StartExportAction extends IndexAction
{
    public function run(): void
    {
        if ($this->controller->request->isAjax) {
            $action = new RunProcessAction('start-export', $this->controller);
            $action->onRunProcess = function () {
                $id = $this->controller->request->post('export_id');
                if (!ctype_digit($id) || strlen($id) !== 10) {
                    throw new BadRequestHttpException('Invalid export ID format');
                }
                $this->performExport($id);
            };
            $action->run();
        }
        Yii::$app->end();
    }

    /**
     * Anything that fails here happens before Exporter::runJob() ever creates
     * an ExportJob record, so without this catch the id never exists in
     * cache: progress-export polls a synthetic "still running" placeholder
     * forever, and the 60s stall-guard eventually closes the SSE connection
     * without ever sending a distinguishable terminal frame - the user sees
     * no error and download-export never fires. Persisting a cancelled job
     * here means the very next progress-export poll gets a real terminal
     * status with the actual error message.
     */
    protected function performExport(string $id): void
    {
        $this->cancelJobOnFatalError($id);
        try {
            $representation = $this->ensureRepresentationCollection()->getByName($this->getUiModel()->representation);
            $request = (new ExportRequestFactory())->fromStartExportAction($this, $representation->getColumns());
            Yii::$app->exporter->runJob($id, $request);
        } catch (Throwable $e) {
            Yii::error('Export failed before it could start: ' . $e->getMessage(), __METHOD__);
            ExportJob::findOrCreate($id)->cancel($e->getMessage());
        }
    }

    /**
     * try/catch cannot see fatal errors (max_execution_time, memory exhaustion): the job would stay RUNNING
     * and the user would wait for nothing. The shutdown function still runs after them and records the cause.
     */
    private function cancelJobOnFatalError(string $id): void
    {
        register_shutdown_function(static function () use ($id): void {
            $job = ExportJob::findOrCreate($id);
            if ($job->isSuccess() || $job->needToTerminate()) {
                return;
            }
            $error = error_get_last();
            $reason = $error !== null ? sprintf('%s in %s:%d', $error['message'], $error['file'], $error['line']) : 'unknown reason';
            Yii::error('Export interrupted: ' . $reason, __METHOD__);
            $job->cancel('The export was interrupted: ' . $reason);
        });
    }
}
