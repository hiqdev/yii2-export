<?php

namespace hiqdev\yii2\export;

interface ExporterInterface
{
    /**
     * Render file content
     *
     * @return string
     */
    public function render();
}

