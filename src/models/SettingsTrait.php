<?php

namespace hiqdev\yii2\export\models;

use hiqdev\yii2\export\exporters\ExporterInterface;

trait SettingsTrait
{
    public function applyTo(ExporterInterface $exporter)
    {
        $exporter->setSettings($this->getAttributes());
    }
}
