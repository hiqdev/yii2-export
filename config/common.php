<?php
/**
 * Data exporting to different formats for Yii2
 *
 * @link      https://github.com/hiqdev/yii2-export
 * @package   yii2-export
 * @license   BSD-3-Clause
 * @copyright Copyright (c) 2017, HiQDev (http://hiqdev.com/)
 */

return [
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
        ]
    ],
    'container' => [
        'definitions' => [
            \hiqdev\yii2\export\exporters\ExporterFactoryInterface::class => [
                ['class' => \hiqdev\yii2\export\exporters\ExporterFactory::class],
                [
                    [
                        \hiqdev\yii2\export\exporters\Type::CSV => \hiqdev\yii2\export\exporters\CsvExporter::class,
                        \hiqdev\yii2\export\exporters\Type::TSV => \hiqdev\yii2\export\exporters\TsvExporter::class,
                        \hiqdev\yii2\export\exporters\Type::XLSX => \hiqdev\yii2\export\exporters\XlsxExporter::class,
                    ],
                ],
            ],
        ],
    ],
];
