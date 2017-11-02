<?php

namespace hiqdev\yii2\export\exporters;

abstract class AbstractExporter
{
    protected $options = [];

    public function __construct($options)
    {
        $this->options = $options;
    }
}
