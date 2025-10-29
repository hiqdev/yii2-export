<?php

declare(strict_types=1);


namespace hiqdev\yii2\export\models;

use hiqdev\yii2\export\helpers\ExportJobStorage;
use hiqdev\yii2\export\helpers\SaveManager;
use RuntimeException;
use Yii;
use yii\base\Model;

class ExportJob extends Model
{
    public ?int $runTs = null;
    public ?int $createTs = null;
    public ?int $finishTs = null;
    public ?string $errorMessage = null;
    public int $progress = 0;
    public int $total = 0;
    public ?string $taskName = null;
    public ?string $unit = null;
    public ?string $mimeType = null;
    public ?string $extension = null;
    public string $status = ExportStatus::NEW->value;

    private string $id;
    private SaveManager $saver;
    private ExportJobStorage $storage;

    public function extraFields(): array
    {
        return ['id', 'status'];
    }

    public function toArray(array $fields = [], array $expand = [], $recursive = true): array
    {
        return parent::toArray($fields, array_merge($expand, ['id', 'status']), $recursive);
    }

    public function init(): void
    {
        $this->createTs = time();

        if (empty($this->id)) {
            throw new RuntimeException('ExportJob id is required');
        }

        if (empty($this->taskName)) {
            $this->taskName = Yii::t('hiqdev.export', 'Initialization');
        }

        $this->initializeStorageAndSaver();
    }

    public static function findOrCreate(string $id): self
    {
        $job = new self(['id' => $id]);

        if ($job->storage->exists()) {
            $content = $job->storage->fetch();
            $job->setAttributes($content, false);
        }

        return $job;
    }

    public function increaseProgress(?int $count = 1): self
    {
        $this->progress += $count;

        return $this;
    }

    public function begin(): void
    {
        $this->updateStatus(ExportStatus::RUNNING);
        $this->runTs = time();
        $this->storage->save();
    }

    public function end(bool $isSuccess = true, ?string $errorMessage = null): void
    {
        $this->finishTs = time();
        $this->updateStatus($isSuccess ? ExportStatus::SUCCESS : ExportStatus::ERROR, $errorMessage);
        $this->storage->save();
    }

    public function cancel(?string $message = null): void
    {
        $this->updateStatus(ExportStatus::CANCEL, $message);
        $this->storage->save();
    }

    public function isAlive(): bool
    {
        return $this->storage->exists();
    }

    public function isNew(): bool
    {
        return $this->status === ExportStatus::NEW->value;
    }

    public function isSuccess(): bool
    {
        return $this->status === ExportStatus::SUCCESS->value;
    }

    public function needToTerminate(): bool
    {
        return in_array(
            ExportStatus::tryFrom($this->status),
            [ExportStatus::SUCCESS, ExportStatus::ERROR, ExportStatus::CANCEL],
            true
        );
    }

    public function delete(): void
    {
        $this->storage->delete();
        $this->saver->delete();
    }

    // Getters
    public function getProgress(): int
    {
        return $this->progress;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function getTaskName(): ?string
    {
        return $this->taskName;
    }

    public function getUnit(): ?string
    {
        return $this->unit;
    }

    public function getSaver(): SaveManager
    {
        return $this->saver;
    }

    // Setters
    public function setTotal(int $total): self
    {
        $this->total = $total;

        return $this;
    }

    public function setTaskName(?string $taskName): self
    {
        $this->taskName = $taskName;

        return $this;
    }

    public function setUnit(?string $unit): self
    {
        $this->unit = $unit;

        return $this;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function commit(): self
    {
        $this->storage->save();

        return $this;
    }

    // Private Helper Methods
    private function initializeStorageAndSaver(): void
    {
        $this->saver = new SaveManager($this);
        $this->storage = new ExportJobStorage($this);
    }

    private function updateStatus(ExportStatus $status, ?string $message = null): void
    {
        $this->status = $status->value;
        $this->finishTs = time();

        if ($message !== null) {
            $this->errorMessage = $message;
        }
    }
}
