<?php declare(strict_types=1);

namespace hiqdev\yii2\export\tests\unit\models;

use hiqdev\yii2\export\models\ExportJob;
use hiqdev\yii2\export\models\ExportStatus;
use PHPUnit\Framework\TestCase;
use Yii;

final class ExportJobTest extends TestCase
{
    private function freshId(): string
    {
        // Same 10-digit numeric shape StartExportAction validates against.
        return (string) random_int(1000000000, 1999999999);
    }

    public function testBeginEndCancelTransitions(): void
    {
        $job = new ExportJob(['id' => $this->freshId()]);
        $this->assertTrue($job->isNew());

        $job->begin();
        $this->assertSame(ExportStatus::RUNNING->value, $job->status);
        $this->assertFalse($job->needToTerminate());

        $job->end();
        $this->assertTrue($job->isSuccess());
        $this->assertTrue($job->needToTerminate());
    }

    public function testEndWithFailureSetsErrorStatus(): void
    {
        $job = new ExportJob(['id' => $this->freshId()]);
        $job->begin();
        $job->end(false, 'boom');

        $this->assertSame(ExportStatus::ERROR->value, $job->status);
        $this->assertSame('boom', $job->errorMessage);
        $this->assertTrue($job->needToTerminate());
    }

    public function testCancelSetsCancelStatus(): void
    {
        $job = new ExportJob(['id' => $this->freshId()]);
        $job->cancel('user cancelled');

        $this->assertSame(ExportStatus::CANCEL->value, $job->status);
        $this->assertTrue($job->needToTerminate());
    }

    public function testCommitThrottledCommitsImmediatelyOnFirstCall(): void
    {
        $id = $this->freshId();
        $job = new ExportJob(['id' => $id]);

        $job->increaseProgress()->commitThrottled();

        $cached = Yii::$app->cache->get(['export-job', $id]);
        $this->assertSame(1, $cached['progress']);
    }

    public function testCommitThrottledSkipsIntermediateRows(): void
    {
        $id = $this->freshId();
        $job = new ExportJob(['id' => $id]);

        $job->increaseProgress()->commitThrottled(); // always commits first time
        $afterFirst = Yii::$app->cache->get(['export-job', $id]);

        for ($i = 0; $i < 10; $i++) {
            $job->increaseProgress()->commitThrottled();
        }
        $afterTen = Yii::$app->cache->get(['export-job', $id]);

        $this->assertSame($afterFirst['progress'], $afterTen['progress'], 'cache must not be touched before the row/time threshold is reached');
        $this->assertSame(11, $job->getProgress(), 'in-memory progress must still advance every call even when the commit is skipped');
    }

    public function testCommitThrottledEventuallyPersistsAfterEnoughRows(): void
    {
        $id = $this->freshId();
        $job = new ExportJob(['id' => $id]);

        // Commits fire on call #1 (lastCommitAt starts null) and then every
        // 50 calls after that (#51, #101, ...) - not continuously - so after
        // 60 calls the cache reflects call #51, not the latest in-memory value.
        for ($i = 0; $i < 60; $i++) {
            $job->increaseProgress()->commitThrottled();
        }

        $cached = Yii::$app->cache->get(['export-job', $id]);
        $this->assertSame(51, $cached['progress'], 'throttle must have flushed again once 50 rows passed since the first commit');
        $this->assertSame(60, $job->getProgress(), 'in-memory progress keeps advancing between throttled flushes');
    }

    public function testCommitAlwaysPersistsRegardlessOfThrottle(): void
    {
        $id = $this->freshId();
        $job = new ExportJob(['id' => $id]);

        $job->increaseProgress()->commitThrottled();
        $job->increaseProgress(); // no commit at all yet for this row
        $job->end(); // unconditional commit - must reflect the latest in-memory progress

        $cached = Yii::$app->cache->get(['export-job', $id]);
        $this->assertSame(2, $cached['progress']);
        $this->assertSame(ExportStatus::SUCCESS->value, $cached['status']);
    }

    public function testRunningJobBecomesStaleOnlyAfterTimeout(): void
    {
        $job = new ExportJob(['id' => $this->freshId()]);
        $job->begin();
        $this->assertFalse($job->isStale(60));

        $job->updateTs = time() - 61;
        $this->assertTrue($job->isStale(60));

        $job->end();
        $this->assertFalse($job->isStale(60), 'a finished job is never stale');
    }
}
