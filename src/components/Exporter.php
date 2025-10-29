<?php

declare(strict_types=1);


namespace hiqdev\yii2\export\components;

use Exception;
use hipanel\actions\IndexAction;
use hipanel\base\Controller;
use hipanel\widgets\SynchronousCountEnabler;
use hiqdev\hiart\ActiveDataProvider;
use hiqdev\yii2\export\actions\StartExportAction;
use hiqdev\yii2\export\exporters\ExporterFactoryInterface;
use hiqdev\yii2\export\exporters\ExporterInterface;
use hiqdev\yii2\export\exporters\ExportType;
use hiqdev\yii2\export\models\ExportJob;
use InvalidArgumentException;
use RuntimeException;
use Yii;
use yii\base\Component;
use yii\data\DataProviderInterface;

class Exporter extends Component
{
    private ?ExportJob $job = null;

    public function __construct(public ExporterFactoryInterface $exporterFactory, $config = [])
    {
        parent::__construct($config);
    }

    public function runJob(string $id, StartExportAction $action, array $representationColumns): void
    {
        $exportHandler = $this->prepareExporter($action, $representationColumns);
        $this->job = ExportJob::findOrNew($id);

        $exportHandler->setExportJob($this->job);

        if (!$this->job->isNew()) {
            // Guard clause for invalid job state
            $this->handleInvalidJobState();

            return;
        }

        $this->initializeJob($exportHandler);

        try {
            $exportHandler->export($this->job);
            $this->job->end();
        } catch (Exception $e) {
            $this->handleExportError($e);
            throw $e;
        }
    }

    private function initializeJob(ExporterInterface $exportHandler): void
    {
        $this->job->begin($exportHandler->getMimeType(), $exportHandler->getExportType()->value);
    }

    private function handleInvalidJobState(): void
    {
        Yii::error('Export: The export job must be STATUS_NEW. ' . $this->job->errorMessage);
    }

    private function handleExportError(Exception $e): void
    {
        $this->job->cancel($e->getMessage());
        Yii::error('Export error: ' . $e->getMessage());
    }

    private function prepareExporter(IndexAction $action, array $representationColumns): ExporterInterface
    {
        $exporter = $this->initializeExporter($action, $representationColumns);
        $exporter->initExportOptions();

        return $exporter;
    }

    public function __destruct()
    {
        if (isset($this->job) && !$this->job->isSuccess()) {
            $this->job->cancel('There was probably an internal error during Report generating. Contact the development team.');
        }
    }

    private function guessGridClassName(Controller $controller): string|RuntimeException
    {
        $controllerName = str_replace(' ', '', ucwords(str_replace('-', ' ', $controller->id)));
        $ns = implode(
            '\\',
            array_diff(explode('\\', get_class($controller)), [
                $controllerName . 'Controller',
                'controllers',
            ])
        );
        $gridClassName = sprintf('\%s\grid\%sGridView', $ns, $controllerName);
        if (class_exists($gridClassName)) {
            return $gridClassName;
        }

        throw new RuntimeException("ExportAction cannot find a $gridClassName");
    }

    private function initializeExporter(IndexAction $action, array $representationColumns): ExporterInterface
    {
        $formatType = $this->getExportFormat($action);
        $exporter = $this->createExporter($formatType, $representationColumns);
        $dataProvider = $this->wrapDataProvider($action);

        $exporter->setDataProvider($dataProvider);
        $exporter->setGridClassName($this->guessGridClassName($action->controller));

        return $exporter;
    }

    private function getExportFormat(IndexAction $action): string
    {
        return $action->controller->request->get('format');
    }

    private function createExporter(string $type, array $representationColumns): ExporterInterface
    {
        $exporter = $this->getExporter($type);
        $exporter->setRepresentationColumns($representationColumns);

        return $exporter;
    }

    private function wrapDataProvider(IndexAction $action): DataProviderInterface
    {
        $dataProvider = $this->getDataProvider($action);
        $enabler = new SynchronousCountEnabler($dataProvider);

        return $enabler->getDataProvider();
    }

    private function getExporter(string $format): ExporterInterface
    {
        $type = ExportType::tryFrom($format);

        return $this->exporterFactory->build($type);
    }

    private function getDataProvider(?IndexAction $action = null): ActiveDataProvider
    {
        if (!$action) {
            throw new InvalidArgumentException('Action cannot be null.');
        }

        $indexConfig = $this->getIndexActionConfig($action);

        if ($indexConfig !== null) {
            $indexAction = $this->createIndexAction($indexConfig, $action);
            $indexAction->beforePerform();

            return $indexAction->getDataProvider();
        }

        return $action->getDataProvider();
    }

    private function getIndexActionConfig(IndexAction $action): ?array
    {
        $actions = $action->controller->actions();

        return $actions['index'] ?? null;
    }

    private function createIndexAction(array $config, IndexAction $action): IndexAction
    {
        $config['forceStorageFiltersApply'] = true;

        return Yii::createObject($config, ['index', $action->controller]);
    }
}
