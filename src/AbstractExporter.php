<?php

namespace hiqdev\yii2\export;

abstract class AbstractExporter
{
    protected $options = [];

    public function __construct($options)
    {
        $this->options = $options;
    }
}
