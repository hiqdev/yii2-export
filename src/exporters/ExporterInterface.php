<?php

namespace hiqdev\yii2\export\exporters;

interface ExporterInterface
{

    /**
     * Render file content
     *
     * @param $grid
     * @param array $columns
     * @return string
     */
    public function export($grid, $columns);
}

