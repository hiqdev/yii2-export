<?php

namespace hiqdev\yii2\export\exporters;

abstract class AbstractExporter
{
    protected $options = [];

    public function __construct($options)
    {
        $this->options = $options;
    }

    /**
     * Prevention execution code on an administrator’s machine in their user’s security context.
     *
     * @param string $row
     * @return string
     */
    protected function sanitizeRow(string $row)
    {
        return ltrim($row, '=+-@');
    }
}
