<?php

declare(strict_types=1);

namespace hiqdev\yii2\export\models;

use hiqdev\yii2\export\components\Exporter;
use hiqdev\yii2\export\exporters\ExporterInterface;
use Yii;
use yii\base\Model;
use function Opis\Closure\serialize;
use function Opis\Closure\unserialize;

/**
 *
 * @property-read string $filename
 */
class ExportJob extends Model
{
    public const string STATUS_NEW = 'new';
    public const string STATUS_RUNNING = 'running';
    public const string STATUS_SUCCESS = 'success';
    public const string STATUS_ERROR = 'error';
    public const string STATUS_CANCEL = 'cancel';

    public ?int $runTs = null;
    public ?int $createTs = null;
    public ?int $finishTs = null;
    public ?string $errorMessage = null;
    public string $status = self::STATUS_NEW;
    public int $progress = 0;
    public int $total = 0;
    public ?string $taskName = null;
    public ?string $unit = null;
    public ?string $extension = null;

    public function init(): void
    {
        $this->createTs = time();
    }

    public function prepare(string $id): void
    {
        $this->id = $id;
    }

    public static function find(string $id): ?self
    {
        $content = Yii::$app->cache->get([$id, 'job']);
        if ($content === false) {
            return null;
        }
        $job = unserialize($content);
        if (!empty($job) && $job instanceof ExportJob) {
            return $job;
        }

        return null;
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

    public function begin(Exporter $exporter): void
    {
        $this->status = self::STATUS_RUNNING;
        $this->runTs = time();

        $exporter->setJob($this->id, $this);
    }

    public function end(Exporter $exporter, bool $isSuccess = true, ?string $errorMessage = null): void
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

    public function run(ExporterInterface $exporter, Exporter $component): void
    {
        $exporter->exportJob = $this;
        $exporter->exporter = $component;
        $saver = new SaveManager($this->id);
        $exporter->export($saver);
    }

    public function getFilename(): string
    {
        return implode('.', ['report_' . $this->id, $this->extension]);
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

    public function isAlive(): true
    {
        if (Yii::$app->cache->exists([$this->id, 'job'])) {
            return true;
        }
        throw new \Exception('Job is not found');
    }

    private function save(): bool
    {
        return Yii::$app->cache->set([$this->id, 'job'], serialize($this), 3600 * 4);
    }
}
