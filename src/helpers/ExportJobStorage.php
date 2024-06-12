<?php declare(strict_types=1);

namespace hiqdev\yii2\export\helpers;

use hiqdev\yii2\export\models\ExportJob;
use yii\caching\CacheInterface;
use Yii;

readonly class ExportJobStorage
{
    private array $key;
    private ?CacheInterface $storage;

    public function __construct(private ExportJob $job)
    {
        $this->key = ['export-job', $job->id];
        $this->storage = Yii::$app->cache;
    }

    public function exists(): bool
    {
        return $this->storage->exists($this->key);
    }

    public function save(): bool
    {
        return $this->storage->set($this->key, $this->job->toArray([], ['id', 'status']), 3600 * 4);
    }

    public function fetch()
    {
        return $this->storage->get($this->key);
    }

    public function delete(): bool
    {
        return $this->storage->delete($this->key);
    }
}
