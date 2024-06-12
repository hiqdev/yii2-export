<?php declare(strict_types=1);

namespace hiqdev\yii2\export\components;

use hipanel\actions\IndexAction;
use hipanel\base\Controller;
use hipanel\widgets\SynchronousCountEnabler;
use hiqdev\hiart\ActiveDataProvider;
use hiqdev\yii2\export\actions\StartExportAction;
use hiqdev\yii2\export\exporters\ExporterFactoryInterface;
use hiqdev\yii2\export\exporters\ExporterInterface;
use hiqdev\yii2\export\exporters\ExportType;
use hiqdev\yii2\export\models\ExportJob;
use hiqdev\yii2\export\models\CsvSettings;
use hiqdev\yii2\export\models\MDSettings;
use hiqdev\yii2\export\models\StaleExportJobException;
use hiqdev\yii2\export\models\TsvSettings;
use hiqdev\yii2\export\models\XlsxSettings;
use RuntimeException;
use yii\base\Component;
use Yii;
use yii\grid\GridView;

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
        $exporter = $this->prepare($action, $representationColumns);
        $job = ExportJob::findOrNew($id);
        if ($job->isNew()) {
            try {
                $exporter->export($job);
                $job->end();
            } catch (StaleExportJobException $e) {
                $job->cancel($e->getMessage());
            } catch (\Exception $e) {
                $job->cancel($e->getMessage());
                Yii::error('Export: ' . $e->getMessage());
            }
        } else {
            Yii::error('Export: The export job must be STATUS_NEW.');
        }
    }

    public function loadSettings($type)
    {
        $map = [
            ExportType::CSV->value => CsvSettings::class,
            ExportType::TSV->value => TsvSettings::class,
            ExportType::XLSX->value => XlsxSettings::class,
            ExportType::MD->value => MDSettings::class,
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
                $controllerName . 'Controller',
                'controllers',
            ]));
        $girdClassName = sprintf('\%s\grid\%sGridView', $ns, $controllerName);
        if (class_exists($girdClassName)) {
            return $girdClassName;
        }

        throw new RuntimeException("ExportAction cannot find a {$girdClassName}");
    }

    private function createExporter(IndexAction $action, array $representationColumns): ExporterInterface
    {
        $type = $action->controller->request->get('format');
        $exporter = $this->exporterFactory->build($type);
        $settings = $this->loadSettings($type);
        $enabler = new SynchronousCountEnabler($this->getDataProvider($action), fn(GridView $grid): string => '');
        $settings?->applyTo($exporter);
        $exporter->setDataProvider($enabler->getDataProvider());
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
