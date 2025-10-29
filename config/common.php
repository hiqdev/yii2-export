<?php
/**
 * Data exporting to different formats for Yii2
 *
 * @link      https://github.com/hiqdev/yii2-export
 * @package   yii2-export
 * @license   BSD-3-Clause
 * @copyright Copyright (c) 2017, HiQDev (http://hiqdev.com/)
 */

use hiqdev\yii2\export\exporters\ExportType;

return [
    'controllerMap' => [
        'exporter' => [
            \hiqdev\yii2\export\commands\ExporterController::class,
        ],
    ],
    'components' => [
        'i18n' => [
            'translations' => [
                'hiqdev.export' => [
                    'class' => \yii\i18n\PhpMessageSource::class,
                    'basePath' => dirname(__DIR__) . '/src/messages',
                ],
            ],
        ],
        'exporter' => [
            'class' => hiqdev\yii2\export\components\Exporter::class,
        ],
    ],
    'container' => [
        'definitions' => [
            \hiqdev\yii2\export\exporters\ExporterFactoryInterface::class => [
                ['class' => \hiqdev\yii2\export\exporters\ExporterFactory::class],
                [
                    [
                        ExportType::CSV->value => \hiqdev\yii2\export\exporters\CSVExporter::class,
                        ExportType::TSV->value => \hiqdev\yii2\export\exporters\TSVExporter::class,
                        ExportType::XLSX->value => \hiqdev\yii2\export\exporters\XLSXExporter::class,
                        ExportType::MD->value => \hiqdev\yii2\export\exporters\MDExporter::class,
                    ],
                ],
            ],
        ],
    ],
];
