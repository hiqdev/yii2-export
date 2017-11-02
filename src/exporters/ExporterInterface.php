<?php

namespace hiqdev\yii2\export\exporters;

interface ExporterInterface
{
    /**
     * Render file content
     *
     * @return string
     */
    public function export();
}

