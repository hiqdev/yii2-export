<?php

declare(strict_types=1);


namespace hiqdev\yii2\export\exporters;

use yii\di\Container;

readonly class ExporterFactory implements ExporterFactoryInterface
{
    public function __construct(
        private array $map,
        private Container $di
    )
    {
    }

    public function build(ExportType $type): ExporterInterface
    {
        return $this->di->get($this->map[$type->value]);
    }
}
