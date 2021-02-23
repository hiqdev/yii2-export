<?php

namespace hiqdev\yii2\export\exporters;

interface ExporterInterface
{

    /**
     * Render file content
     *
     * @param $grid
     * @return string
     */
    public function export($grid);
}

