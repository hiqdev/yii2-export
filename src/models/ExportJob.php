<?php declare(strict_types=1);

namespace hiqdev\yii2\export\models;

use hiqdev\yii2\export\helpers\ExportJobStorage;
use hiqdev\yii2\export\helpers\SaveManager;
use hiqdev\yii2\export\models\enums\ExportStatus;
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

    public function toArray(array $fields = [], array $expand = [], $recursive = true)
    {
        return parent::toArray($fields, array_merge($expand, ['id', 'status']), $recursive);
    }

    public function init(): void
    {
        $this->createTs = time();
        $this->status = ExportStatus::NEW->value;
        if (!$this->id) {
            throw new RuntimeException('ExportJob id is required');
        }
        if (empty($this->taskName)) {
            $this->taskName = Yii::t('hiqdev.export', 'Initialization');
        }
        $this->saver = new SaveManager($this);
        $this->storage = new ExportJobStorage($this);
    }

    public static function findOrNew(string $id): self
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

    public function begin(string $mimeType, string $extension): void
    {
        $this->mimeType = $mimeType;
        $this->extension = $extension;
        $this->status = ExportStatus::RUNNING->value;
        $this->runTs = time();
        $this->storage->save();
    }

    public function end(bool $isSuccess = true, ?string $errorMessage = null): void
    {
        $this->finishTs = time();
        if ($isSuccess) {
            $this->status = ExportStatus::SUCCESS->value;
        } else {
            $this->status = ExportStatus::ERROR->value;
            $this->errorMessage = $errorMessage;
        }
        $this->storage->save();
    }

    public function cancel(?string $message = null): void
    {
        $this->finishTs = time();
        $this->status = ExportStatus::CANCEL->value;
        $this->errorMessage = $message;
        $this->storage->save();
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function setTotal(int $total): self
    {
        $this->total = $total;

        return $this;
    }

    public function getTaskName(): ?string
    {
        return $this->taskName;
    }

    public function setTaskName(?string $taskName): self
    {
        $this->taskName = $taskName;

        return $this;
    }

    public function getUnit(): ?string
    {
        return $this->unit;
    }

    public function setUnit(?string $unit): self
    {
        $this->unit = $unit;

        return $this;
    }

    public function isNew(): bool
    {
        return ExportStatus::tryFrom($this->status) === ExportStatus::NEW;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getSaver(): SaveManager
    {
        return $this->saver;
    }

    public function commit(): self
    {
        $this->storage->save();

        return $this;
    }

    public function isAlive(): bool
    {
        return $this->storage->exists();
    }

    public function delete(): void
    {
        $this->storage->delete();
        $this->saver->delete();
    }
}
