<?php

namespace hiqdev\yii2\export\models;

class CsvSettings extends \yii\base\Model
{
    public $csvFileConfig = [
        'cellDelimiter' => "\t",
        'rowDelimiter' => "\n",
        'enclosure' => '',
    ];
}
