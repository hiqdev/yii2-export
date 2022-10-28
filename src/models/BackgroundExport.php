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

    protected $id;
    protected $runTs;
    protected $createTs;
    protected $finishTs;
    protected $errorMessage;
    protected string $status = self::STATUS_NEW;
    protected int $progress = 0;

    public function __construct(?string $id = null)
    {
        $this->id = $id ?? md5(uniqid('', true));
        $this->createTs = time();
    }

    public function increaseProgress(): void
    {
        $this->progress++;
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
        $cache = Yii::$app->cache;
        $exporter->exportJob = $this;
        $exporter->exporter = $component;
        if ($cache->exists([$this->id, 'report'])) {
            return true;
        }
        $data = $exporter->export();

        return $cache->set(
            [$this->id, 'report'],
            ['data' => $data, 'mimeType' => $exporter->getMimeType(), 'fileName' => $exporter->filename],
            180
        );
    }
}
