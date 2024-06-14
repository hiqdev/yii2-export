<?php declare(strict_types=1);

namespace hiqdev\yii2\export\models\enums;

enum ExportStatus: string
{
    case RUNNING = 'running';
    case SUCCESS = 'success';
    case ERROR = 'error';
    case CANCEL = 'cancel';
    case NEW = 'new';
}
