<?php declare(strict_types=1);

namespace hiqdev\yii2\export\models;

use yii\base\Model;

class XlsxSettings extends Model
{
    use SettingsTrait;

    public function rules()
    {
        return [];
    }
}
