<?php

declare(strict_types=1);

namespace hiqdev\yii2\export\components;

use hipanel\actions\IndexAction;
use hipanel\base\Controller;
use hipanel\grid\GridView;
use hiqdev\hiart\ActiveDataProvider;
use hiqdev\yii2\export\actions\StartExportAction;
use hiqdev\yii2\export\exporters\ExporterFactoryInterface;
use hiqdev\yii2\export\exporters\ExporterInterface;
use hiqdev\yii2\export\exporters\Type;
use hiqdev\higrid\DataColumn;
use hiqdev\yii2\export\models\ExportJob;
use hiqdev\yii2\export\models\CsvSettings;
use hiqdev\yii2\export\models\MDSettings;
use hiqdev\yii2\export\models\StaleExportJobException;
use hiqdev\yii2\export\models\TsvSettings;
use hiqdev\yii2\export\models\XlsxSettings;
use RuntimeException;
use yii\base\Component;
use yii\base\Exception;
use Yii;

class Exporter extends Component
{
    public function __construct(public ExporterFactoryInterface $exporterFactory, $config = [])
    {
        parent::__construct($config);
    }

    public function prepare(IndexAction $action, array $representationColumns): ExporterInterface
    {
        $exporter = $this->createExporter($action, $representationColumns);
        $exporter->initExportOptions();

        return $exporter;
    }

    public function runJob(string $id, StartExportAction $action, array $representationColumns): void
    {
        fastcgi_finish_request(); // required for PHP-FPM (PHP > 5.3.3)
        $exporter = $this->prepare($action, $representationColumns);
        $job = new ExportJob();
        if ($this->setJob($id, $job)) {
            $job = $this->getJob($id);
            if ($job->getStatus() === ExportJob::STATUS_NEW) {
                $job->begin($this);
                try {
                    $job->run($exporter, $this);
                    $job->endJob($this);
                } catch (StaleExportJobException $e) {
                    $job->cancel($this, false, $e->getMessage());
                } catch (\Exception $e) {
                    $job->end($this, false, $e->getMessage());
                    Yii::error('Export: ' . $e->getMessage());
                }
            } else {
                Yii::warning('Export: The export job has no STATUS_NEW.');
                throw new Exception("The export job has no STATUS_NEW.");
            }
        }
    }

    public function isExistsJob(string $jobId): bool
    {
        return Yii::$app->cache->exists([$jobId, 'job']);
    }

//    public function getJob(string $jobId): ExportJob
//    {
//        $content = Yii::$app->cache->get([$jobId, 'job']);
//        if ($content === false) {
//            throw new Exception("Couldn't get content from job $jobId.");
//        }
//        $job = unserialize($content);
//
//        if ($job) {
//            if ($job instanceof ExportJob) {
//                return $job;
//            }
//            throw new Exception("Failed job class in $jobId.");
//        }
//        throw new Exception("Failed unserialize job file $jobId.");
//    }
//
//    public function setJob(string $jobId, ExportJob $job): bool
//    {
//        return Yii::$app->cache->set([$jobId, 'job'], serialize($job), 3600 * 4);
//    }

    public function loadSettings($type)
    {
        $map = [
            Type::CSV->value => CsvSettings::class,
            Type::TSV->value => TsvSettings::class,
            Type::XLSX->value => XlsxSettings::class,
            Type::MD->value => MDSettings::class,
        ];

        $settings = Yii::createObject($map[$type]);
        if ($settings->load(Yii::$app->request->get(), '') && $settings->validate()) {
            return $settings;
        }

        return null;
    }

    private function guessGridClassName(Controller $controller): string|RuntimeException
    {
        $controllerName = ucfirst($controller->id);
        $ns = implode('\\',
            array_diff(explode('\\', get_class($controller)), [
                $controllerName . 'Controller', 'controllers',
            ]));
        $girdClassName = sprintf('\%s\grid\%sGridView', $ns, $controllerName);
        if (class_exists($girdClassName)) {
            return $girdClassName;
        }

        throw new RuntimeException("ExportAction cannot find a {$girdClassName}");
    }

    private function createGrid(Controller $controller, array $columns): GridView
    {
        $gridClassName = $this->guessGridClassName($controller);
        $indexAction = null;
        if (isset($controller->actions()['index'])) {
            $indexActionConfig = $controller->actions()['index'];
            $indexAction = Yii::createObject($indexActionConfig, ['index', $controller]);
            $indexAction->beforePerform();
        }
        $dataProvider = $indexAction ? $indexAction->getDataProvider() : $this->getDataProvider();
        $grid = Yii::createObject([
            'class' => $gridClassName,
            'dataProvider' => $dataProvider,
            'columns' => $columns,
        ]);
        $grid->dataColumnClass = DataColumn::class;

        return $grid;
    }

    private function createExporter(IndexAction $action, array $representationColumns): ExporterInterface
    {
        $type = $action->controller->request->get('format');
        $exporter = $this->exporterFactory->build($type);
        $settings = $this->loadSettings($type);
        $settings?->applyTo($exporter);
        $exporter->setDataProvider($this->getDataProvider($action));
        $exporter->setGridClassName($this->guessGridClassName($action->controller));
        $exporter->setRepresentationColumns($representationColumns);

        return $exporter;
    }

    private function getDataProvider(?IndexAction $action = null): ActiveDataProvider
    {
        $indexAction = null;
        if ($action && isset($action->controller->actions()['index'])) {
            $indexActionConfig = $action->controller->actions()['index'];
            $indexActionConfig['forceStorageFiltersApply'] = true;
            $indexAction = Yii::createObject($indexActionConfig, ['index', $action->controller]);
            $indexAction->beforePerform();
        }

        return $indexAction ? $indexAction->getDataProvider() : $action->getDataProvider();
    }
}
