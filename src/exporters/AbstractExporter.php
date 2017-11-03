<?php

namespace hiqdev\yii2\export\exporters;

abstract class AbstractExporter
{
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

    public function getFileName()
    {
        return static::class;
    }
}
