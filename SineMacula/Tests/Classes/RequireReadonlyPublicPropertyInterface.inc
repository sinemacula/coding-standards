<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;

class ProcessPodcast implements ShouldQueue
{
    public int $tries = 3;

    public int $timeout = 120;

    public int $backoff = 10;

    public int $triesLeft = 3;

    public string $status = 'new';

    public function __construct(
        public int $maxExceptions,
        public string $podcast,
    ) {
    }
}

class SendInvoice implements \Illuminate\Contracts\Queue\ShouldQueue
{
    public int $tries = 5;
}

class ExportReport implements Arrayable, ShouldQueue
{
    public int $timeout = 60;
}

class BroadcastOrder implements ShouldBroadcast
{
    public string $connection = 'pusher';

    public int $tries = 1;
}

class RenderView implements Renderable
{
    public int $tries = 1;
}

class QueuedChild extends ProcessPodcast
{
    public int $tries = 1;
}

class DeferredMail implements ShouldQueueAfterCommit
{
    public int $tries = 1;
}

trait RetriesJobs
{
    public int $tries = 1;
}
