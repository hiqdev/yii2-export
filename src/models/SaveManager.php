<?php

declare(strict_types=1);

namespace hiqdev\yii2\export\models;

use Yii;
use yii\helpers\FileHelper;

class SaveManager
{
    private string $path = '@runtime/export-reports';

    public function __construct(private readonly string $id)
    {
        FileHelper::createDirectory($this->getPath());
    }

    public function save(string $payload): bool
    {
        $path = $this->getPath();
        if (FileHelper::createDirectory($path)) {
            return file_put_contents($path . DIRECTORY_SEPARATOR . $this->id, $payload, LOCK_EX) !== false;
        }

        return false;
    }

    public function delete(): bool
    {
        return unlink($this->getFilePath());
    }

    /**
     * @return false|resource
     */
    public function getStream(string $mimeType)
    {
        return fopen('data://' . $mimeType . ';base64,' . base64_encode($this->getContent()), 'rb');
    }

    public function getContent(): string
    {
        return file_get_contents($this->getFilePath());
    }

    public function getFilePath(): string
    {
        return $this->getPath() . DIRECTORY_SEPARATOR . $this->id;
    }

    private function getPath(): string
    {
        return Yii::getAlias($this->path);
    }
}
