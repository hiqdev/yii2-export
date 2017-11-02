<?php

namespace hiqdev\yii2\export\exporters;

interface ExporterFactoryInterface
{
    public function build($type);
}
