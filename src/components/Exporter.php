<?php declare(strict_types=1);

namespace hiqdev\yii2\export\components;

use Exception;
use hiqdev\yii2\export\exporters\ExporterFactoryInterface;
use hiqdev\yii2\export\exporters\ExporterInterface;
use hiqdev\yii2\export\exporters\ExportType;
use hiqdev\yii2\export\models\ExportCancelledException;
use hiqdev\yii2\export\models\ExportJob;
use Yii;
use yii\base\Component;

class Exporter extends Component
{
    private ?ExportJob $job = null;

    public function __construct(public ExporterFactoryInterface $exporterFactory, $config = [])
    {
        parent::__construct($config);
    }

    public function runJob(string $id, ExportRequest $request): void
    {
        $this->job = ExportJob::findOrCreate($id);

        try {
            $exportHandler = $this->prepareExporter($request);
        } catch (Exception $e) {
            Yii::error('Export error: ' . $e->getMessage());
            $this->handleExportError($e);
            return;
        }

        $exportHandler->setExportJob($this->job);

        if (!$this->job->isNew()) {
            // Guard clause for invalid job state
            $this->handleInvalidJobState();

            return;
        }

        $this->job->begin();

        try {
            $exportHandler->export($this->job);
            $this->job->end();
        } catch (ExportCancelledException $e) {
            $this->job->cancel($e->getMessage());
            $this->job->getSaver()->delete();
        } catch (Exception $e) {
            $this->handleExportError($e);
            throw $e;
        }
    }

    /**
     * Reached when a start-export retry reuses an id whose job is already
     * running/finished (export buttons render with a fixed id, so a second
     * click after a failed attempt hits this - confirmed in production
     * logs). Previously this only logged and left the job exactly as it
     * was, so a stuck/stale job could never be retried: cancel it so the
     * next attempt has a clean terminal state to start from.
     */
    private function handleInvalidJobState(): void
    {
        Yii::error('Export: The export job must be STATUS_NEW. ' . $this->job->errorMessage);
        $this->job->cancel('A previous export attempt with this ID did not finish cleanly; cancelled to allow retry.');
    }

    private function handleExportError(Exception $e): void
    {
        $this->job->cancel($e->getMessage());
        Yii::error('Export error: ' . $e->getMessage());
    }

    private function prepareExporter(ExportRequest $request): ExporterInterface
    {
        $exporter = $this->initializeExporter($request);
        $exporter->initExportOptions();

        return $exporter;
    }

    public function __destruct()
    {
        // a job that already ended (e.g. cancelled with the real error) must keep its own message
        if (isset($this->job) && !$this->job->needToTerminate()) {
            $this->job->cancel('There was probably an internal error during Report generating. Contact the development team.');
        }
    }

    private function initializeExporter(ExportRequest $request): ExporterInterface
    {
        $exporter = $this->createExporter($request->format, $request->representationColumns);
        $exporter->setDataProvider($request->dataProvider);
        $exporter->setGridClassName($request->gridClassName);

        return $exporter;
    }

    private function createExporter(string $type, array $representationColumns): ExporterInterface
    {
        $exporter = $this->getExporter($type);
        $exporter->setRepresentationColumns($representationColumns);

        return $exporter;
    }

    private function getExporter(string $format): ExporterInterface
    {
        $type = ExportType::tryFrom($format);

        return $this->exporterFactory->build($type);
    }
}
