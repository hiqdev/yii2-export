<?php

namespace hiqdev\yii2\export\models;

class CsvSettings extends \yii\base\Model
{
    use SettingsTrait;

    public $fieldDelimiter = ",";

    public $fieldEnclosure = '"';

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['fieldDelimiter', 'fieldEnclosure'], 'string'],
        ];
    }
}
