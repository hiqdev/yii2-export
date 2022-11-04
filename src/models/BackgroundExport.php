<?php

declare(strict_types=1);

namespace hiqdev\yii2\export\models;

use hiqdev\yii2\export\components\Exporter;
use hiqdev\yii2\export\exporters\ExporterInterface;
use Yii;

class BackgroundExport
{
    public const STATUS_NEW = 'new';
    public const STATUS_RUNNING = 'running';
    public const STATUS_ERROR = 'error';
    public const STATUS_SUCCESS = 'success';

    protected $runTs;
    protected $createTs;
    protected $finishTs;
    protected $errorMessage;
    protected string $status = self::STATUS_NEW;
    protected int $progress = 0;

    public function __construct(protected string $id, public string $mimeType, public string $extension)
    {
        $this->createTs = time();
    }

    public function increaseProgress(?int $count = 1): void
    {
        $this->progress += $count;
        Yii::$app->exporter->setJob($this->id, $this);
    }

    public function getProgress(): int
    {
        return $this->progress;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function beginJob(Exporter $exporter): void
    {
        $this->status = self::STATUS_RUNNING;
        $this->runTs = time();

        $exporter->setJob($this->id, $this);
    }

    public function endJob(Exporter $exporter, bool $isSuccess = true, ?string $errorMessage = null): void
    {
        $this->finishTs = time();

        if ($isSuccess) {
            $this->status = self::STATUS_SUCCESS;
        } else {
            $this->status = self::STATUS_ERROR;
            $this->errorMessage = $errorMessage;
        }

        $exporter->setJob($this->id, $this);
    }

    public function deleteJob(): bool
    {
        return Yii::$app->cache->delete([$this->id, 'job']);
    }

    public function run(ExporterInterface $exporter, Exporter $component): bool
    {
        $exporter->exportJob = $this;
        $exporter->exporter = $component;
        $saver = new SaveManager($this->id);
        try {
            $exporter->export($saver);
        } catch (\Exception $e) {
            Yii::warning('Export: '. $e->getMessage());
            return false;
        }

        return true;
    }

    public function getFilename(): string
    {
        return $this->id . '.' . $this->extension;
    }
}
