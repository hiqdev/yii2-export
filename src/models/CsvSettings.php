<?php declare(strict_types=1);

namespace hiqdev\yii2\export\models;

use yii\base\Model;

class CsvSettings extends Model
{
    use SettingsTrait;

    public string $fieldDelimiter = ",";
    public string $fieldEnclosure = '"';

    public function rules()
    {
        return [
            [['fieldDelimiter', 'fieldEnclosure'], 'string'],
        ];
    }
}
