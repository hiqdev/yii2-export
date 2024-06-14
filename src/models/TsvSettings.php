<?php declare(strict_types=1);

namespace hiqdev\yii2\export\models;

class TsvSettings extends CsvSettings
{
    public string $fieldDelimiter = "\t";
}
