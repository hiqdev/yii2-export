<?php

namespace hiqdev\yii2\export\exporters;

use yii\di\Container;

class ExporterFactory implements ExporterFactoryInterface
{
    protected $map;

    protected $di;

    public function __construct($map, Container $di)
    {
        $this->map = $map;
        $this->di = $di;
    }

    public function build($type)
    {
        return $this->di->get($this->map[$type]);
    }
}
